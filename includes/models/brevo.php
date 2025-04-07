<?php

require_once VENDOR_DIR . "/autoload.php";

use GuzzleHttp\Client;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\GetSendersListSenders;

$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

class Brevo
{
    private GetSendersListSenders $sender;
    private $apiEmailCampaigns;
    private $apiLists;
    private $apiSenders;
    private $apiTransactionalEmails;
    private $apiContacts;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $_ENV['BREVO_API_KEY']);
        $path = (DIRECTORY_SEPARATOR === '\\')
            ? str_replace('/', '\\', VENDOR_DIR . '\cacert.pem')
            : str_replace('\\', '/', VENDOR_DIR . '\cacert.pem');
        $client = new Client([
            'verify' => $path
        ]);

        $this->apiEmailCampaigns = new \Brevo\Client\Api\EmailCampaignsApi(
            $client,
            $config
        );
        $this->apiLists = new \Brevo\Client\Api\ListsApi(
            $client,
            $config
        );
        $this->apiContacts = new \Brevo\Client\Api\ContactsApi(
            $client,
            $config
        );
        $this->apiTransactionalEmails = new \Brevo\Client\Api\TransactionalEmailsApi(
            $client,
            $config
        );
        $this->apiSenders = new \Brevo\Client\Api\SendersApi(
            $client,
            $config
        );

        $this->getSender();
    }

    private function getSender()
    {
        try {
            $listSenders = $this->apiSenders->getSenders();
            foreach ($listSenders->getSenders() as $sender) {
                if ($sender->getName() == EMAIL_NAME_SENDER) {
                    $this->sender = $sender;
                    return;
                }
            }
        } catch (\RuntimeException $e) {
            throw new \RuntimeException("Brevo API: {$e->getMessage()}");
        }
    }

    private function _sendEmailTransaccional(SendSmtpEmail $data): string
    {
        try {
            $res = $this->apiTransactionalEmails->sendTransacEmail($data)->getMessageId();
            return $res;
        } catch (\RuntimeException $e) {
            throw new \RuntimeException("Brevo API: {$e->getMessage()}");
        }
    }

    public function sendIndividualEmail(Student $contact, string $subject, string $content)
    {
        $name = $contact->getName() . ' ' . $contact->getLastName();
        $email = $contact->getEmail();
        $sendSmtpEmail = new SendSmtpEmail([
            'subject' => $subject,
            'sender' => new \Brevo\Client\Model\SendSmtpEmailSender(['id' => $this->sender['id']]),
            'to' => [['name' => $name, 'email' => $email]],
            'htmlContent' => $content
        ]);
        return $this->_sendEmailTransaccional($sendSmtpEmail);
    }
}
