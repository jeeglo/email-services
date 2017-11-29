<?php

interface ServiceInterface
{
    public function getLists();
    public function addContact($data = []);
}
