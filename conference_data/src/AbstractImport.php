<?php
namespace WorldSpeakers;

abstract class AbstractImport implements ImportInterface
{
    public function importFile($fileName)
    {
        $fh = fopen($fileName, 'r');

        if(!$fh){
            throw new \InvalidArgumentException('could not open file for reading: `' . $fileName . '`');
        }

        $header = fgetcsv($fh);

        while($row = fgetcsv($fh)){
            $data = array_combine($header, $row);
            $data = $this->normalizeData($data);
            $this->importLine($data);
        }

        $this->finish();
    }

    protected function normalizeData(array $data)
    {
        $normal = [];

        $normal['talk'] = [
            'title'       => $data['title'],
            'description' => $data['description'],
            'keywords'    => $data['keywords'],
            'date'  => new \DateTime($data['day'] . ' ' . $data['start'], new \DateTimeZone('America/New_York')),
            'room'  => $data['room'],
            'type'  => $data['type'],
            'level' => $data['level'],
        ];

        $normal['speaker'] = [
            'name'    => $data['name'],
            'company' => $data['company'],
            'bio'     => $data['bio'],
            'social' => [
                'twitter'  => $data['Twitter'],
                'facebook' => $data['Facebook'],
                'linkedin' => $data['Linkedin']
            ]
        ];

        return $normal;
    }

    abstract protected function importLine(array $data);

    abstract protected function finish();
}