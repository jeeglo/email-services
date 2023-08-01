<?php

namespace Jeeglo\EmailService;
use Jeeglo\EmailService\Drivers\FunnelMates as FunnelMates;
use Jeeglo\EmailService\Drivers\Mailchimp as Mailchimp;
use Jeeglo\EmailService\Drivers\ActiveCampaign as ActiveCampaign;
use Jeeglo\EmailService\Drivers\GetResponse as GetResponse;
use Jeeglo\EmailService\Drivers\iContact as iContact;
use Jeeglo\EmailService\Drivers\Mailerlite as Mailerlite;
use Jeeglo\EmailService\Drivers\Mailvio as Mailvio;
use Jeeglo\EmailService\Drivers\SendFox as SendFox;
use Jeeglo\EmailService\Drivers\SendInBlue as SendInBlue;
use Jeeglo\EmailService\Drivers\Sendlane as Sendlane;
use Jeeglo\EmailService\Drivers\ConvertKit as ConvertKit;
use Jeeglo\EmailService\Drivers\Drip as Drip;
use Jeeglo\EmailService\Drivers\ConstantContact as ConstantContact;
use Jeeglo\EmailService\Drivers\Aweber as Aweber;
use Jeeglo\EmailService\Drivers\Kyvio as Kyvio;
use Jeeglo\EmailService\Drivers\Ontraport as Ontraport;
use Jeeglo\EmailService\Drivers\EmailOctopus as EmailOctopus;
use Jeeglo\EmailService\Drivers\Sendiio as Sendiio;
use Jeeglo\EmailService\Drivers\Infusionsoft as Infusionsoft;
use Jeeglo\EmailService\Drivers\Moosend as Moosend;
use Jeeglo\EmailService\Drivers\Mautic as Mautic;
use Jeeglo\EmailService\Drivers\CampaignRefinery;
use Jeeglo\EmailService\Drivers\SendGrid;
use Jeeglo\EmailService\Drivers\AweberOauth2 as AweberOauth2;
use Jeeglo\EmailService\Drivers\PlatformLy as PlatformLy;
use Jeeglo\EmailService\Drivers\Acumbamail as Acumbamail;
use Jeeglo\EmailService\Drivers\Vbout as Vbout;

class EmailService {

	protected $driver;

	public function __construct($service, $credentials = []) {

    	switch ($service) {

            case 'aweber':

                $this->driver = new Aweber($credentials);
                break;

            case 'aweberOauth2':

                $this->driver = new AweberOauth2($credentials);
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

            case 'ontraport':
                $this->driver = new Ontraport($credentials);
                break;

            case 'emailOctopus':
                $this->driver = new EmailOctopus($credentials);
                break;

            case 'sendiio':
                $this->driver = new Sendiio($credentials);
                break;

            case 'mailvio':
                $this->driver = new Mailvio($credentials);
                break;

            case 'infusionsoft':
                $this->driver = new Infusionsoft($credentials);
                break;

            case 'sendFox':
                $this->driver = new SendFox($credentials);
                break;

            case 'moosend':
                $this->driver = new Moosend($credentials);
                break;

            case 'mautic':
                $this->driver = new Mautic($credentials);
                break;

            case 'campaignRefinery':
                $this->driver = new CampaignRefinery($credentials);
                break;

            case 'sendGrid':
                $this->driver = new SendGrid($credentials);
                break;

            case 'platformLy':
                $this->driver = new PlatformLy($credentials);
                break;

            case 'sendInBlue':
                $this->driver = new SendInBlue($credentials);
                break;

            case 'funnelMates':
                $this->driver = new FunnelMates($credentials);
                break;

            case 'acumbamail':
                $this->driver = new Acumbamail($credentials);
                break;

            case 'vbout':
                $this->driver = new Vbout($credentials);
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
     * [getTags for current driver]
     * @param array $data for mailchimp driver but null for other drivers
     * @return [array] [key value]
     */
    public function getTags($data = null)
    {
        if(!empty($data)){
            return $this->driver->getTags($data);
        }

        return $this->driver->getTags();
    }

    /**
     * [addContact for adding user to current driver]
     * @param [type] $data [description]
     */
    public function addContact($data, $remove_tags = [], $add_tags = [])
    {
        return $this->driver->addContact($data, $remove_tags, $add_tags);
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

    /**
     * Regenerate/Refresh Access/Refresh Tokens from Email service
     * @param [type] $data [description]
     */
    public function regenrateAccessToken($refresh_token)
    {
        return $this->driver->regenrateAccessToken($refresh_token);
    }

    /**
     * Verify test credentials
     * @return false|string|void
     */
    public function verifyCredentials()
    {
        return $this->driver->verifyCredentials();
    }
}