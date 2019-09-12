<?php

 // include('email-services/src/services/Mailchimp.php');
namespace Jeeglo\EmailService;
use Jeeglo\EmailService\Drivers\Mailchimp as Mailchimp;
use Jeeglo\EmailService\Drivers\ActiveCampaign as ActiveCampaign;
use Jeeglo\EmailService\Drivers\GetResponse as GetResponse;
use Jeeglo\EmailService\Drivers\iContact as iContact;
use Jeeglo\EmailService\Drivers\Mailerlite as Mailerlite;
use Jeeglo\EmailService\Drivers\Sendlane as Sendlane;
use Jeeglo\EmailService\Drivers\ConvertKit as ConvertKit;
use Jeeglo\EmailService\Drivers\Drip as Drip;
use Jeeglo\EmailService\Drivers\ConstantContact as ConstantContact;
use Jeeglo\EmailService\Drivers\Aweber as Aweber;
use Jeeglo\EmailService\Drivers\Kyvio as Kyvio;

class EmailService {

	protected $driver;

	public function __construct($service, $credentials = []) {
        
        // $this->driver = $service;
    	switch ($service) {
    		case 'aweber':
                $this->driver = new Aweber($credentials);
                break;

            case 'activeCampaign':
                $this->driver = new ActiveCampaign($credentials);
                break;

            case 'constantContact':
                $this->driver = new ConstantContact($credentials);
                break;

            case 'getResponse':
                $this->driver = new GetResponse($credentials);
                break;

            case 'iContact':
                $this->driver = new iContact($credentials);
                break;

            case 'mailchimp':
                $this->driver = new Mailchimp($credentials);
                break;

            case 'sendlane':
                $this->driver = new Sendlane($credentials);
                break;

            case 'convertKit':
                $this->driver = new ConvertKit($credentials);
                break;

            case 'mailerlite':
                $this->driver = new Mailerlite($credentials);
                break;

            case 'drip':
                $this->driver = new Drip($credentials);
                break;

            case 'kyvio':
                $this->driver = new Kyvio($credentials);
    			break;
    		
    		default:
    			return 'Not Found';
    			break;
    	}
   	}

	/**
     * [getLists for current driver]
     * @return [array] [key value]
     */
    public function getLists()
	{
        return $this->driver->getLists();
    }

    /**
     * [addContact for adding user to current driver]
     * @param [type] $data [description]
     */
    public function addContact($data)
    {
		return $this->driver->addContact($data);
    }

    /**
     * Connect Email service
     * @param [type] $data [description]
     */
    public function connect()
    {
        return $this->driver->connect();
    }

    /**
     * Get Connect Data from Email service
     * @param [type] $data [description]
     */
    public function getConnectData()
    {
        return $this->driver->getConnectData();
    }
 
}