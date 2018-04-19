<?php
namespace WorldApi;

use Crell\ApiProblem\ApiProblem;
use Nocarrier\Hal;
use Psr\Http\Message\RequestInterface;
use WorldSpeakers\SourceInterface;
use Zend\Diactoros\Response\TextResponse;

class Api
{
    /**
     * @var array
     */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function handleRequest(RequestInterface $request)
    {

        // Just a stub, but could represent an account / application
        $token = $this->getAuth($request);

        if($token instanceof ApiProblem){
            return $token;
        }

        $route = $this->matchRoute($request);

        if($route instanceof ApiProblem){
            return $route;
        }

        $method = 'handle' . ucfirst($route);

        if(!is_callable([$this, $method])){
            $problem = new ApiProblem('Could Not Route', 'https://httpstatusdogs.com/500');
            $problem->setStatus(500);
            $problem->setDetail('Internal routing missing for valid route: ' . $route);
            return $problem;
        }

        $hal = $this->$method($request);

        if(!($hal instanceof Hal)){
            $problem = new ApiProblem('Could Not Render', 'https://httpstatusdogs.com/500');
            $problem->setStatus(500);
            $problem->setDetail('Internal resource rendering failed.');
            return $problem;
        }

        return new TextResponse($hal->asJson(), 200, [
            'Content-Type' => 'application/hal+json'
        ]);

    }

    protected function getAuth(RequestInterface $request)
    {
        $problem = new ApiProblem('Unauthorized', 'https://httpstatusdogs.com/401-unauthorized');
        $problem->setStatus(401);

        $token = $request->getHeaderLine('Authorization');

        if(!$token){
            $problem->setDetail('Missing Authorization Header');
            return $problem;
        }

        if(strpos($token, 'Bearer') !== 0){
            $problem->setDetail('Invalid Authorization Header');
            return $problem;
        }

        $token = substr($token, 7);

        if(!in_array($token, $this->config['tokens'])){
            $problem->setDetail('Invalid Token');
            return $problem;
        }
    }

    protected function matchRoute(RequestInterface $request)
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if('/' == $path){
            return 'entrypoint';
        }

        $segments = explode('/', trim($path, '/'));

        if('talks' == $segments[0]){
            return 'talks';
        }

        if('speakers' == $segments[0]){
            return 'speakers';
        }

        $problem = new ApiProblem('Not Found', 'https://httpstatusdogs.com/404');
        $problem->setStatus(404);
        $problem->setDetail('Invalid URI: ' . $path);

        return $problem;
    }

    protected function handleEntrypoint(RequestInterface $request)
    {
        $entrypoint = new Hal('/');
        $entrypoint->addLink('talks', (string) $request->getUri()->withPath('/talks'), [
            'title' => 'List Talks'
        ]);
        $entrypoint->addLink('speakers', (string) $request->getUri()->withPath('/speakers'), [
            'title' => 'List Speakers'
        ]);

        return $entrypoint;
    }

    protected function handleTalks(RequestInterface $request)
    {
        // This is our data layer, it's not that great.
        $source = $this->getDataSource();

        // See if this request had an ID, something the route could also do.
        $id = $this->getId($request);

        // If a specific talk was requested, return that. A better router would
        // route collection and resource requests to two different methods.
        if($id){
            // Our data layer throws exceptions, so catch the one for a bad ID.
            try{
                $talk = $source->getTalkById($id);
            } catch (\InvalidArgumentException $e) {
                // Let the user know their ID was bad.
                $problem = new ApiProblem('Talk Not Found', 'https://httpstatusdogs.com/404');
                $problem->setStatus(404);
                $problem->setDetail('Could not find talk for ID: ' . $id);
                return $problem;
            }

            // Get the API representation of our data. This could be done by
            // something like a hydrator. We don't want to tie out output
            // directly to the database, because that's not structured best for
            // API clients.
            $data = $this->getRepresentationForTalk($talk);

            // Get the speaker, because we know HAL expects it.
            $speaker = $source->getSpeakerById($talk['speaker']);

            // Take our data representation, the resource ID (used to create the
            // URI), the speaker (as HAL wants to link to them), and the request
            // (so we know how to assemble the URI), and create a HAL object we
            // can render as output.
            return $this->getHalForTalk($talk['id'], $data, $speaker, $request);
        }

        // Ensure that we have the default paging parameters, and get the talks.
        $params = $this->getDefaultParams($request);
        $talks = $source->getTalks($params['page'], $params['size']);

        // Create a HAL objct for our collection, it's data is just the count.
        $collection = new Hal((string) $request->getUri(), [
            'count' => count($talks)
        ]);

        // Add next, prev, first links.
        $this->addPagingLinks($collection, $request, $params, count($talks));

        // Convert all the talks into HAL objects, using the same process as
        // returning a single talk.
        foreach($talks as $talk)
        {
            $data = $this->getRepresentationForTalk($talk);
            $speaker = $source->getSpeakerById($talk['speaker']);
            $resource = $this->getHalForTalk($talk['id'], $data, $speaker, $request);

            // Add the talks to the collection, and ensure that if there's only
            // one on the page it's still an array as expected.
            $collection->addResource('talks', $resource, true);
        }

        // Return the hAL object to be output.
        return $collection;
    }

    protected function handleSpeakers(RequestInterface $request)
    {
        // This is our data layer, it's not that great.
        $source = $this->getDataSource();

        // See if this request had an ID, something the route could also do.
        $id = $this->getId($request);

        // If a specific talk was requested, return that. A better router would
        // route collection and resource requests to two different methods.
        if($id){
            // Our data layer throws exceptions, so catch the one for a bad ID.
            try{
                $speaker = $source->getSpeakerById($id);
            } catch (\InvalidArgumentException $e) {
                // Let the user know their ID was bad.
                $problem = new ApiProblem('Speaker Not Found', 'https://httpstatusdogs.com/404');
                $problem->setStatus(404);
                $problem->setDetail('Could not find speaker for ID: ' . $id);
                return $problem;
            }

            $data = $this->getRepresentationForSpeaker($speaker);

            return $this->getHalForSpeaker($speaker['id'], $data, $request);
        }

        // Ensure that we have the default paging parameters, and get the talks.
        $params = $this->getDefaultParams($request);
        $speakers = $source->getSpeakers($params['page'], $params['size']);

        // Create a HAL objct for our collection, it's data is just the count.
        $collection = new Hal((string) $request->getUri(), [
            'count' => count($speakers)
        ]);

        // Add next, prev, first links.
        $this->addPagingLinks($collection, $request, $params, count($speakers));

        // Convert all the talks into HAL objects, using the same process as
        // returning a single talk.
        foreach($speakers as $speaker)
        {
            $data = $this->getRepresentationForSpeaker($speaker);
            $resource = $this->getHalForSpeaker($speaker['id'], $data, $request);

            // Add the talks to the collection, and ensure that if there's only
            // one on the page it's still an array as expected.
            $collection->addResource('speakers', $resource, true);
        }

        // Return the hAL object to be output.
        return $collection;

    }

    /**
     * Get any ID (second segment of the URI).
     *
     * @param RequestInterface $request
     * @return string|null
     */
    protected function getId(RequestInterface $request)
    {
        $path = $request->getUri()->getPath();
        $parts = explode('/', trim($path, '/'));
        if(isset($parts[1]) && !empty($parts[1])){
            return $parts[1];
        }
    }

    /**
     * Based on the current query parameters, and the count of the page, set the
     * next, prev, first links. Next and prev are only set if needed.
     *
     * @param Hal $collection
     * @param RequestInterface $request
     * @param array $params
     * @param integer $count
     */
    protected function addPagingLinks(Hal $collection, RequestInterface $request, array $params, $count)
    {
        $new = $params;
        $new['page'] = 0;
        $collection->addLink('first', (string) $request->getUri()->withQuery(http_build_query($new)));

        if($params['page'] > 0){
            $new = $params;
            $new['page']--;

            $collection->addLink('prev', (string) $request->getUri()->withQuery(http_build_query($new)));
        }

        if($count == $params['size']){
            $new = $params;
            $new['page']++;

            $collection->addLink('next', (string) $request->getUri()->withQuery(http_build_query($new)));
        }
    }

    /**
     * Merge the provided parameters with the default parameters. This would be
     * a great place to do some parameter validation / whitelisting.
     *
     * @param RequestInterface $request
     * @return array
     */
    protected function getDefaultParams(RequestInterface $request)
    {
        $params = [];
        parse_str($request->getUri()->getQuery(), $params);
        $params = array_merge($this->config['default']['paging'], $params);
        return $params;
    }

    /**
     * Create a HAL object from the API representation of the talk, needs a
     * speaker and the request to build the speaker link. Needs the talk id and
     * request to build the self link.
     *
     * @param $id
     * @param $data
     * @param $speaker
     * @param RequestInterface $request
     * @return Hal
     */
    protected function getHalForTalk($id, $data, $speaker, RequestInterface $request)
    {
        $resource = new Hal(
            $request->getUri()->withPath('/talks/') . $id,
            $data
        );

        $resource->addLink(
            'speakers',
            $request->getUri()->withPath('/speakers/') . $speaker['id'],
            [
                'title' => $speaker['name']
            ],
            true
        );

        return $resource;
    }

    /**
     * Take the flat array the data layer gives us and turn it into something
     * more useful for API clients. Try to think of how the data will likely
     * change.
     *
     * @param array $talk
     * @return array
     */
    protected function getRepresentationForTalk($talk)
    {
        $data = [
            'title' => $talk['title'],
            'description' => $talk['description'],
            'keywords' => [],
            'date' => (new \DateTime('@' . $talk['date']))->format('c'),
            'room' => $talk['room'],
            'type' => $this->normalizeType($talk['type']),
            'level' => $this->normalizeLevel($talk['level']),
        ];

        foreach(explode(',', $talk['keywords']) as $keyword)
        {
            $data['keywords'][] = trim($keyword);
        }

        return $data;
    }

    /**
     * Create a HAL object from the API representation of the speaker. Needs the
     * speaker id and request to build the self link.
     *
     * @param $id
     * @param $data
     * @param $speaker
     * @param RequestInterface $request
     * @return Hal
     */
    protected function getHalForSpeaker($id, $data, RequestInterface $request)
    {
        $resource = new Hal(
            $request->getUri()->withPath('/speaker/') . $id,
            $data
        );

        return $resource;
    }

    /**
     * Take the flat array the data layer gives us and turn it into something
     * more useful for API clients. Try to think of how the data will likely
     * change.
     *
     * @param array $talk
     * @return array
     */
    protected function getRepresentationForSpeaker($speaker)
    {
        $data = [
            'name' => $speaker['name'],
        ];

        return $data;
    }

    /**
     * Our data layer is pretty bad. The API spec defines a set of types, this
     * ensures we don't use any other strings.
     *
     * @param string $type
     * @return string
     */
    protected function normalizeType($type)
    {
        switch(strtolower(substr($type, 0, 3))){
            case 'tra':
                return 'training';
            case 'wor':
                return 'workshop';
        }

        return 'session';
    }

    /**
     * Our data layer is pretty bad. The API spec defines a set of levels, this
     * ensures we don't use any other strings.
     *
     * @param string $level
     * @return string
     */
    protected function normalizeLevel($level)
    {
        switch(strtolower(substr($level, 0, 3))){
            case 'beg':
                return 'beginner';
            case 'int':
                return 'intermediate';
            case 'adv':
                return 'advanced';
        }

        return 'all';
    }

    /**
     * Get the data service, based on the config.
     *
     * @return SourceInterface
     */
    protected function getDataSource()
    {
        $class = $this->config['driver']['class'];
        $args  = $this->config['driver']['args'];

        $class = new \ReflectionClass($class);
        $source = $class->newInstanceArgs($args);

        return $source;
    }


}