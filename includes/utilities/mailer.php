<?php

require_once '../../../includes/config/constants.php';
require_once INCLUDES_DIR . '/utilities/util.php';
require_once INCLUDES_DIR . '/utilities/handleErrors.php';
require_once INCLUDES_DIR . '/models/brevo.php';

use Spatie\Mjml\Mjml;

class Mailer
{
    private Brevo $brevo;
    private Student $contact;
    private string $htmlContent;
    private string $subject;
    private string $template;
    private string $url;

    public function __construct(Student $contact, string $subject, string $template)
    {
        $this->brevo = new Brevo();
        $this->htmlContent = '';
        $this->contact = $contact;
        $this->subject = $subject;
        $this->template = $template;
    }

    private function getTemplateHTML(): string
    {
        $baseHTML = file_get_contents(EMAIL_TEMPLATES_DIR . "/". $this->template. ".mjml");
        if ($baseHTML === false) {
            throw new \RuntimeException('Error reading base template!');
        }
        return $baseHTML;
    }

    private function convertMJMLToHTML(string $mjml): string
    {
        $htmlObj = Mjml::new()->beautify()->convert($mjml);
        if ($htmlObj->hasErrors()) {
            $e = "";
            foreach ($htmlObj->errors() as $error) {
                if ($error->line() === null || $error->line() === 0) {
                    continue;
                }
                $e .= $error->formattedMessage() . "\n";
            }
            throw new \RuntimeException("Error converting to MJML <$e>");
        }
        return $htmlObj->html();
    }

    private function saveToken(int $studentID, string $token): bool
    {
        $tokenDB = getToken($studentID);
        if ($tokenDB == $token) {
            return true;
        }
        return ($tokenDB == "") ? insertToken($studentID, $token) : updateToken($studentID, $token);
    }

    public function constructEmail()
    {
        try {

            /*Por modificar*/
            /*----------------------------------------------------------------------------*/
            $token = bin2hex(random_bytes(32));
            $save = $this->saveToken($this->contact->getID(), $token);
            if (!$save) {
                return false;
            }
            $url = filePathToUrl(MODULES_DIR . "/" . $this->url . "/confirmation.php");
            /*----------------------------------------------------------------------------*/

            $lastNameParts = preg_split('/\s+/', $this->contact->getLastName());
            $formattedLastName = implode(' ', array_map('ucfirst', $lastNameParts));
            $dataReplace = [
                "program" => ucfirst($this->contact->getProgram()),
                "name" => ucfirst($this->contact->getName()) . " " . $formattedLastName,
                "url" => "$url?token=$token", /*--> Posible modificación*/
            ];
            $base = $this->getTemplateHTML();
            $keys = array_map(fn ($key) => "-- ". strtoupper($key). " --", array_keys($dataReplace));
            $this->htmlContent = $this->convertMJMLToHTML($base);
            $this->htmlContent = str_replace($keys, array_values($dataReplace), $this->htmlContent);
            return true;
        } catch (RuntimeException $e) {
            throw new \RuntimeException("Error constructing email: {$e->getMessage()}");
        }
    }

    public function send()
    {
        $res = $this->brevo->sendIndividualEmail($this->contact, $this->subject, $this->htmlContent);
        if ($res != "") {
            return [
                "messageID" => $res,
                "delivered" => "true",
                "receipt" => $this->contact->getEmail()
            ];
        }
        ErrorList::add("Error sending email to: ". $this->contact->getEmail());
        throw new \RuntimeException("Error sending email to: ". $this->contact->getEmail());
    }

    public function setUrl(string $url)
    {
        $this->url = filePathToUrl($url);
    }
}
