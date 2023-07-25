<?php

require_once dirname(dirname(__FILE__)) . '/base/Vbout.php';
require_once dirname(dirname(__FILE__)) . '/base/VboutException.php';

class ApplicationWS extends Vbout 
{
	protected function init()
	{
		$this->api_url = '/app/';
	}
	
    public function getBusinessInfo()
    {	
		$result = array();
		
		try {
			$business = $this->me();

            if ($business != null && isset($business['data'])) {
                $result = array_merge($result, $business['data']['business']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
}