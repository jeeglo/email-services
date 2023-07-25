<?php

require_once dirname(dirname(__FILE__)) . '/base/Vbout.php';
require_once dirname(dirname(__FILE__)) . '/base/VboutException.php';

class WebhookWS extends Vbout 
{
	protected function init()
	{
		$this->api_url = '/webhook/';
	}
	
	public function getWebhooks($params = array())
    {	
		$result = array();
		
		try {
			$webhooks = $this->lists($params);

            if ($webhooks != null && isset($webhooks['data'])) {
                $result = array_merge($result, $webhooks['data']['webhooks']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getWebhook($params = array())
    {	
		$result = array();
		
		try {
			$webhook = $this->show($params);

            if ($webhook != null && isset($webhook['data'])) {
                $result = array_merge($result, $webhook['data']['item']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function removeWebhook($params = array())
    {	
		$result = array();
		
		try {
			$webhook = $this->delete($params);

            if ($webhook != null && isset($webhook['data'])) {
                $result = $webhook['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function addNewWebhook($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$webhook = $this->add($params);

            if ($webhook != null && isset($webhook['data'])) {
                $result = $webhook['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function updateWebhook($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$webhook = $this->edit($params);

            if ($webhook != null && isset($webhook['data'])) {
                $result = $webhook['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }

}