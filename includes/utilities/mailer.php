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
    private string $redirection;
    private string $url;
    private bool $needToken = false;

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
        $baseHTML = file_get_contents(EMAIL_TEMPLATES_DIR . "/" . $this->template . ".mjml");
        if ($baseHTML === false) {
            throw new \RuntimeException('Error reading base template!');
        }
        return $baseHTML;
    }

    private function convertMJMLToHTML(string $mjml): string
    {
        try {
            $options = ['filePath' => realpath(EMAIL_TEMPLATES_DIR)];
            $htmlObj = Mjml::new()->beautify()->convert($mjml, $options);

            if ($htmlObj->hasErrors()) {
                $e = "";
                foreach ($htmlObj->errors() as $error) {
                    $e .= $error->formattedMessage() . "\n";
                }
                throw new \RuntimeException("Error converting MJML: $e");
            }
            return $htmlObj->html();
        } catch (\Throwable $th) {
            throw new \RuntimeException("MJML conversion failed: " . $th->getMessage());
        }
    }

    private function saveToken(int $studentID, string $token): bool
    {
        $tokenDB = getToken($studentID);
        if ($tokenDB == $token) {
            return true;
        }
        return $tokenDB == "" ? insertToken($studentID, $token) : updateToken($studentID, $token);
    }

    public function constructEmail()
    {
        try {
            if ($this->needToken) {
                $token = bin2hex(random_bytes(32));
                if (!$this->saveToken($this->contact->getID(), $token)) {
                    return false;
                }
                $this->url = "$this->redirection?token=" . urlencode($token);
            }

            $lastNameParts = preg_split('/\s+/', $this->contact->getLastName());
            $formattedLastName = implode(' ', array_map('ucfirst', $lastNameParts));

            // FIXME: Maybe is best to receive this as a parameter
            $dataReplace = [
                "program" => ucfirst($this->contact->getProgram()),
                "name" => ucfirst($this->contact->getName()) . " " . $formattedLastName,
                "url" => $this->url ?? "",
            ];
            $base = $this->getTemplateHTML();
            $keys = array_map(fn ($key) => "-- " . strtoupper($key) . " --", array_keys($dataReplace));

            $this->htmlContent = $this->convertMJMLToHTML($base);
            $this->htmlContent = str_replace($keys, array_values($dataReplace), $this->htmlContent);
            return true;
        } catch (\RuntimeException $e) {
            throw new \RuntimeException("Error constructing email: {$e->getMessage()}");
        }
    }

    public function send()
    {
        if ($res = $this->brevo->sendIndividualEmail($this->contact, $this->subject, $this->htmlContent) != "") {
            return [
                "messageID" => $res,
                "delivered" => "true",
                "receipt" => $this->contact->getEmail()
            ];
        }
        ErrorList::add("Error sending email to: " . $this->contact->getEmail());
        return [];
    }

    public function setRedirection(string $filepath)
    {
        if (!file_exists($filepath)) {
            throw new \RuntimeException("File does not exist: $filepath");
        }
        $this->redirection = filePathToUrl($filepath);
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function setNeedToken(bool $needToken)
    {
        $this->needToken = $needToken;
    }
}
