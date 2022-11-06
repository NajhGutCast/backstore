<?php

require_once 'Activity.model.php';
require_once 'Invoice.model.php';
class Evidences
{
    public int $id;
    public string $name;
    public string $mime_type;
    public string $activity;
    public string $folder;
    public int $status;

    public function toArray(): array {
        $json = json_encode($this);
        $array = json_decode($json, true);
        return $array;
    }

    public function getId(): int {
        return $this -> id;
    }
    public function setId(int $id): void {
        $this -> id = $id;
    }

    public function getName(): string {
        return $this -> name;
    }
    public function setName(string $name): void {
        $this -> name = $name;
    }

    public function getMime_type(): string {
        return $this -> mime_type;
    }
    public function setMime_type(string $mime_type): void {
        $this -> mime_type = $mime_type;
    }

    public function getActivity(): string {
        return $this -> activity;
    }
    public function setActivity(string $activity): void {
        $this -> activity = $activity;
    }

    public function getFolder(): string {
        return $this -> folder;
    }
    public function setFolder(string $folder): void {
        $this -> folder = $folder;
    }

    public function getStatus(): string {
        return $this -> status;
    }
    public function setStatus(string $status): void {
        $this -> status = $status;
    }
}
