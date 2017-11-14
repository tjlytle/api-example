<?php
namespace WorldSpeakers;

interface SourceInterface
{
    public function getSpeakers($page, $size);

    public function getSpeakerById($id);

    public function getTalks($page, $size, \DateTime $after = null, \DateTime $before = null);

    public function getTalkById($id);

    public function setFavoriteTalk($user, $id);

    public function setFavoriteSpeaker($user, $id);

    public function getFavorites($user);
}