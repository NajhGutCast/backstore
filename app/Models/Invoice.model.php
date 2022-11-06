<?php


class Invoice
{
    public int $id;
    public string $relative_id;
    public string $issue_date;

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

    public function getRelative_id(): int {
        return $this -> relative_id;
    }
    public function setRelative_id(int $relative_id): void {
        $this -> relative_id = $relative_id;
    }

    public function getIssue_date(): int {
        return $this -> issue_date;
    }
    public function setIssue_date(int $issue_date): void {
        $this -> issue_date = $issue_date;
    }

}
