<?php

require_once dirname(dirname(__FILE__)) . '/base/Vbout.php';
require_once dirname(dirname(__FILE__)) . '/base/VboutException.php';

class SocialMediaWS extends Vbout 
{
	protected function init()
	{
		$this->api_url = '/socialmedia/';
	}
	
	public function getCalendar($params = array())
    {	
		$result = array();
		
		try {
			$calendar = $this->calendar($params);

            if ($calendar != null && isset($calendar['data'])) {
                $result = array_merge($result, $calendar['data']['items']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getChannels()
    {	
		$result = array();
		
		try {
			$channels = $this->channels();

            if ($channels != null && isset($channels['data'])) {
                $result = array_merge($result, $channels['data']['channels']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getMyPost($params = array())
    {	
		$result = array();
		
		try {
			$post = $this->getpost($params);

            if ($post != null && isset($post['data'])) {
                $result = array_merge($result, $post['data']['item']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getStats($params = array())
    {	
		$result = array();
		
		try {
			$stats = $this->stats($params);

            if ($stats != null && isset($stats['data'])) {
                $result = array_merge($result, $stats['data']['stats']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function removePost($id = NULL)
    {	
		$result = array();
		
		try {
			$post = $this->deletepost(array('id'=>$id));

            if ($post != null && isset($post['data'])) {
                $result = $post['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function addNewPost($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$post = $this->addpost($params);
			
            if ($post != null && isset($post['data'])) {
                $result = $post['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
}