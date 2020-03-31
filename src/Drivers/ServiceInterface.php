<?php

interface ServiceInterface
{
    public function getLists();
    public function addContact($data = [], $remove_tags = [], $add_tags = []);
}
