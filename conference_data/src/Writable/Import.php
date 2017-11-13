<?php
namespace WorldSpeakers\Writable;

use WorldSpeakers\AbstractImport;
use WorldSpeakers\ImportInterface;

class Import extends AbstractImport implements ImportInterface
{
    /**
     * @var \SQLite3
     */
    protected $db;

    /**
     * @var \SQLite3Stmt
     */
    protected $speaker;

    /**
     * @var \SQLite3Stmt
     */
    protected $talk;

    /**
     * @var \SQLite3Stmt
     */
    protected $checkSpeaker;

    public function __construct($filename, $sqlPath)
    {
        $this->db = new \SQLite3($filename);
        $this->db->exec(file_get_contents($sqlPath . '/create.sql'));

        $this->speaker = $this->db->prepare(file_get_contents($sqlPath . '/add-speaker.sql'));
        $this->talk    = $this->db->prepare(file_get_contents($sqlPath . '/add-talk.sql'));

        $this->checkSpeaker = $this->db->prepare("SELECT id FROM speakers WHERE id = :id");
    }

    protected function importLine(array $data)
    {
        $speaker_id = md5($data['speaker']['name']);
        $talk_id    = md5($data['talk']['title']);

        $this->checkSpeaker->bindValue(':id', $speaker_id);
        $result = $this->checkSpeaker->execute();

        if(empty($result->fetchArray())){
            $data['speaker'] = array_merge($data['speaker'], $data['speaker']['social']);
            unset($data['speaker']['social']);

            $this->speaker->bindValue('id', $speaker_id);

            foreach($data['speaker'] as $key => $value){
                $this->speaker->bindValue($key, $value);
            }

            $this->speaker->execute();
            $this->speaker->reset();
        }

        $data['talk']['date'] = $data['talk']['date']->format('Y-m-d H:i:s');

        foreach($data['talk'] as $key => $value){
            $this->talk->bindValue($key, $value);
        }

        $this->talk->bindValue('id', $talk_id);
        $this->talk->bindValue('speaker_id', $speaker_id);

        $this->talk->execute();
        $this->talk->reset();
    }

    protected function finish()
    {
        $this->db->close();
    }
}