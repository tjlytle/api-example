<?php
namespace WorldSpeakers\ReadOnly;

use WorldSpeakers\AbstractImport;
use WorldSpeakers\ImportInterface;

class Import extends AbstractImport implements ImportInterface
{
    protected $output;

    protected $speakers = [];

    protected $talks = [];

    public function __construct($dataFile)
    {
        $fh = fopen($dataFile, 'w');

        if(!$fh){
            throw new \InvalidArgumentException('could not open file for writing: `' . $dataFile . '`');
        }

        $this->output = $fh;
    }

    protected function importLine(array $data)
    {
        $speaker_id = md5($data['speaker']['name']);
        $talk_id    = md5($data['talk']['title']);

        $data['speaker']['id'] = $speaker_id;
        $data['talk']['id'] = $talk_id;

        if(!isset($this->speakers[$speaker_id])){
            $this->speakers[$speaker_id] = $data['speaker'];
            $this->speakers[$speaker_id]['talks'] = [];
        }

        $this->speakers[$speaker_id]['talks'][] = $talk_id;

        $data['talk']['date'] =$data['talk']['date']->getTimestamp();

        $this->talks[$talk_id] = $data['talk'];
        $this->talks[$talk_id]['speaker'] = $speaker_id;
    }

    protected function finish()
    {
        uasort($this->talks, function($a, $b){
            return $a['date'] > $b['date'];
        });

        $data = [
            'speakers' => $this->speakers,
            'talks'    => $this->talks,
        ];

        fputs($this->output, json_encode($data, JSON_PRETTY_PRINT));
        fclose($this->output);
    }

}