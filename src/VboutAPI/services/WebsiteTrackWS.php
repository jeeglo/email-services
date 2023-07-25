<?php

require_once dirname(dirname(__FILE__)) . '/base/Vbout.php';
require_once dirname(dirname(__FILE__)) . '/base/VboutException.php';

class WebsiteTrackWS extends Vbout 
{
	protected function init()
	{
		$this->api_url = '/domain/';
	}
	
	public function getDomains()
    {	
		$result = array();
		
		try {
			$domains = $this->get();

            if ($domains != null && isset($domains['data'])) {
                $result = array_merge($result, $domains['data']['items']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
}