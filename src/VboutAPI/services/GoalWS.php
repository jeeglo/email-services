<?php

require_once dirname(dirname(__FILE__)) . '/base/Vbout.php';
require_once dirname(dirname(__FILE__)) . '/base/VboutException.php';

class GoalWS extends Vbout 
{
	protected function init()
	{
		$this->api_url = '/goal/';
	}
	
	public function getGoals($params = array())
    {	
		$result = array();
		
		try {
			$goals = $this->lists($params);

            if ($goals != null && isset($goals['data'])) {
                $result = array_merge($result, $goals['data']['goals']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
		
	public function getGoalsByDomain($params = array())
    {	
		$result = array();
		
		try {
			$goals = $this->listbydomain($params);

            if ($goals != null && isset($goals['data'])) {
                $result = array_merge($result, $goals['data']['goals']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getGoal($params = array())
    {	
		$result = array();
		
		try {
			$goal = $this->show($params);

            if ($goal != null && isset($goal['data'])) {
                $result = array_merge($result, $goal['data']['item']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function removeGoal($params = array())
    {	
		$result = array();
		
		try {
			$goal = $this->delete($params);

            if ($goal != null && isset($goal['data'])) {
                $result = $goal['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function addNewGoal($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$goal = $this->add($params);

            if ($goal != null && isset($goal['data'])) {
                $result = $goal['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function updateGoal($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$goal = $this->edit($params);

            if ($goal != null && isset($goal['data'])) {
                $result = $goal['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
}