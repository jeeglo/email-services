<?php

require_once dirname(dirname(__FILE__)) . '/base/Vbout.php';
require_once dirname(dirname(__FILE__)) . '/base/VboutException.php';

class EmailMarketingWS extends Vbout 
{
	protected function init()
	{
		$this->api_url = '/emailmarketing/';
	}
	
	public function getCampaigns($params = array())
    {	
		$result = array();
		
		try {
			$campaigns = $this->campaigns($params);

            if ($campaigns != null && isset($campaigns['data'])) {
                $result = array_merge($result, $campaigns['data']['campaigns']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getMyCampaign($params = array())
    {	
		$result = array();
		
		try {
			$campaign = $this->getcampaign($params);

            if ($campaign != null && isset($campaign['data'])) {
                $result = array_merge($result, $campaign['data']['item']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function removeCampaign($params = array())
    {	
		$result = array();
		
		try {
			$campaign = $this->deletecampaign($params);

            if ($campaign != null && isset($campaign['data'])) {
                $result = $campaign['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function addNewCampaign($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$campaign = $this->addcampaign($params);

            if ($campaign != null && isset($campaign['data'])) {
                $result = $campaign['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function updateCampaign($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$campaign = $this->editcampaign($params);

            if ($campaign != null && isset($campaign['data'])) {
                $result = $campaign['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getMyLists()
    {	
		$result = array();
		
		try {
			$lists = $this->getlists();

            if ($lists != null && isset($lists['data'])) {
                $result = array_merge($result, $lists['data']['lists']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getMyList($id = NULL)
    {	
		$result = array();
		
		try {
			$list = $this->getlist(array('id'=>$id));

            if ($list != null && isset($list['data'])) {
                $result = array_merge($result, $list['data']['list']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function addNewList($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$list = $this->addlist($params);

            if ($list != null && isset($list['data'])) {
                $result = $list['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function removeList($id = NULL)
    {	
		$result = array();
		
		try {
			$list = $this->deletelist(array('id'=>$id));

            if ($list != null && isset($list['data'])) {
                $result = $list['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function updateList($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$list = $this->editlist($params);

            if ($list != null && isset($list['data'])) {
                $result = $list['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getMyContacts($listId = NULL)
    {	
		$result = array();
		
		try {
			$contacts = $this->getcontacts(array('listid'=>$listId));

            if ($contacts != null && isset($contacts['data'])) {
                $result = array_merge($result, $contacts['data']['contacts']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function searchContact($email = NULL, $listid = NULL)
    {	
		$result = array();
		
		try {
			$contact = $this->getcontactbyemail(array('email'=>$email, 'listid'=>$listid));

            if ($contact != null && isset($contact['data'])) {
                $result = array_merge($result, $contact['data']['contact']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getMyContact($id = NULL)
    {	
		$result = array();
		
		try {
			$contact = $this->getcontact(array('id'=>$id));

            if ($contact != null && isset($contact['data'])) {
                $result = array_merge($result, $contact['data']['contact']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function addNewContact($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$contact = $this->addcontact($params);

            if ($contact != null && isset($contact['data'])) {
                $result = $contact['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function updateContact($params = array())
    {	
		$result = array();
		
		try {
			$this->set_method('POST');
			
			$contact = $this->editcontact($params);

            if ($contact != null && isset($contact['data'])) {
                $result = $contact['data']['item'];
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function removeContact($id = NULL)
    {	
		$result = array();
		
		try {
			$contact = $this->deletecontact(array('id'=>$id));

            if ($contact != null && isset($contact['data'])) {
                $result = $contact['data']['item'];
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
                $result = array_merge($result, $stats['data']['item']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
    }
	
	public function getMyForms()
	{
		$result = array();
		
		try {
			$forms = $this->getforms();

            if ($forms != null && isset($forms['data'])) {
                $result = array_merge($result, $forms['data']['forms']);
            }

		} catch (VboutException $ex) {
			$result = $ex->getData();
        }
		
       return $result;
	}
}