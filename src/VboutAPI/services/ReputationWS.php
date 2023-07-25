<?php

require_once dirname(dirname(__FILE__)) . '/base/Vbout.php';
require_once dirname(dirname(__FILE__)) . '/base/VboutException.php';

class ReputationWS extends Vbout 
{
	protected function init()
	{
		$this->api_url = '/reputation/';
	}
	
	public function getDirectReviews($params = array())
    {	
		$result = array();
		
		try {
			$reviews = $this->direct($params);

            if ($reviews != null && isset($reviews['data'])) {
                $result = array_merge($result, $reviews['data']['reviews']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getOnlineReviews($params = array())
    {	
		$result = array();
		
		try {
			$reviews = $this->online($params);

            if ($reviews != null && isset($reviews['data'])) {
                $result = array_merge($result, $reviews['data']['reviews']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getMyFeedback($id = NULL)
    {	
		$result = array();
		
		try {
			$review = $this->getfeedack(array('id'=>$id));

            if ($review != null && isset($review['data'])) {
                $result = array_merge($result, $review['data']['item']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getSentiments($params = array())
    {	
		$result = array();
		
		try {
			$sentiment = $this->sentiment($params);

            if ($sentiment != null && isset($sentiment['data'])) {
                $result = array_merge($result, $sentiment['data']['sentiment']);
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
	
	public function removeFeedback($id = NULL)
    {	
		$result = array();
		
		try {
			$review = $this->deletefeedback(array('id'=>$id));

            if ($review != null && isset($review['data'])) {
                $result = $review['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
}