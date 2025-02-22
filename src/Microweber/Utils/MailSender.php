<?php

namespace Microweber\Utils;

$_mw_email_transport_object = false;
api_expose_admin('Microweber/Utils/MailSender/test');

use Config;
use View;
use Swift_Mailer;
use Swift_Message;
use Swift_TransportException;
use Illuminate\Support\Facades\Mail;


class MailSender
{
    public $transport = false;
    public $debug = false;
    public $silent_exceptions = false;
    public $cc = false;
    
    // SMTP DETAILS
    public $smtp_host = false;
    public $smtp_port = false;
    public $smtp_username = false;
    public $smtp_password = false;
    public $smtp_auth = false;
    public $smtp_secure = false;
    
    // MAIL DETAILS
    public $email_from = false;
    public $email_from_name = false;
    public $email_no_cache = false;
    public $email_cc = false;
    public $email_reply_to = false;
    public $email_attachments = array();
    public $email_add_hostname_to_subject = false;
    
    private $here = false;

    public function __construct()
    {
        $views = MW_PATH . 'Views' . DS;

        View::addNamespace('mw_email_send', $views);

        $email_from = mw()->option_manager->get('email_from_name', 'email');
        if ($email_from == false or trim($email_from) == '') {
            $email_from = getenv('USERNAME');
        }
        $this->email_from_name = $email_from;

        $this->smtp_host = trim(mw()->option_manager->get('smtp_host', 'email'));
        $this->smtp_port = intval(mw()->option_manager->get('smtp_port', 'email'));

        $this->smtp_username = trim(mw()->option_manager->get('smtp_username', 'email'));
        $this->smtp_password = trim(mw()->option_manager->get('smtp_password', 'email'));
        $this->smtp_auth = trim(mw()->option_manager->get('smtp_auth', 'email'));
        $this->transport = trim(mw()->option_manager->get('email_transport', 'email'));

        $sec = mw()->option_manager->get('smtp_secure', 'email');

        $this->smtp_secure = intval($sec);

        $email_from = mw()->option_manager->get('email_from', 'email');

        if ($email_from == false or trim($email_from) == '') {
            if ($this->email_from_name != '') {
                $email_from = ($this->email_from_name) . '@' . mw()->url_manager->hostname();
            } else {
                $email_from = 'noreply@' . mw()->url_manager->hostname();
            }
            $email_from = str_replace(' ', '-', $email_from);
        }
        $this->email_from = $email_from;

        $this->here = dirname(__FILE__);

        Config::set('mail.from.name', $this->email_from_name);
        Config::set('mail.from.address', $this->email_from);

        Config::set('mail.username', $this->smtp_username);
        Config::set('mail.password', $this->smtp_password);

        if ($this->transport == '' or $this->transport == 'php') {
            Config::set('mail.driver', 'mail');

            $disabled_functions = @ini_get('disable_functions');
            // check if  escapeshellcmd() has been disabled
            if (strstr($disabled_functions, 'escapeshellarg')) {
                //if disabled, switch mail transporter
                $transport = \Microweber\Utils\lib\mail\Swift_MailTransport::newInstance();

                // set new swift mailer
                \Mail::setSwiftMailer(new \Swift_Mailer($transport));
            }

        }

        if ($this->transport == 'gmail') {
            Config::set('mail.host', 'smtp.gmail.com');
            Config::set('mail.port', 587);
            Config::set('mail.encryption', 'tls');
        } else {
            Config::set('mail.host', $this->smtp_host);
            Config::set('mail.port', $this->smtp_port);
            Config::set('mail.encryption', $this->smtp_auth);
        }
    }
    
    public function setEmailTo($email) {
    	$this->email_to = $email;
    }
    
    public function setEmailSubject($subject) {
    	$this->email_subject = $subject;
    }

    public function setEmailMessage($message) {
    	$this->email_message = $message;
    }
    
    public function setEmailHostnameToSubject($hostname) {
    	$this->email_add_hostname_to_subject = $hostname;
    }
    
    public function setEmailNoCache($cache) {
    	$this->email_no_cache = $cache;
    }
    
    public function setEmailCc($cc) {
    	$this->email_cc = $cc;
    }
    
    public function setEmailFrom($email) {
    	$this->email_from = $email;
    }
    public function setEmailFromName($name) {
    	$this->email_from_name = $name;
    }
    public function setEmailReplyTo($replyTo) {
    	$this->email_reply_to = $replyTo;
    }
    
    public function setEmailAttachments($attachments) {
    	$this->email_attachments = $attachments;
    }
    
    /**
     * Send email
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param boolean $add_hostname_to_subject
     * @param boolean $no_cache
     * @param boolean $cc
     * @param boolean $email_from
     * @param boolean $from_name
     * @param boolean $reply_to
     * @param array $attachments
     * @return boolean
     */
    public function send(
        $to = false,
        $subject = false,
        $message = false,
        $add_hostname_to_subject = false,
        $no_cache = false,
        $cc = false,
        $email_from = false,
        $from_name = false,
        $reply_to = false,
    	$attachments = array()
    )
    {
    	if (empty($to)) {
    		$to = $this->email_to;
    	}
    	
    	if (empty($subject)) {
    		$subject = $this->email_subject;
    	}
    	
    	if (empty($message)) {
    		$message = $this->email_message;
    	}
    	
    	if (empty($add_hostname_to_subject)) {
    		$add_hostname_to_subject = $this->email_add_hostname_to_subject;
    	}
    	
    	if (empty($no_cache)) {
    		$no_cache = $this->email_no_cache;
    	}
    	
    	if (empty($cc)) {
    		$cc = $this->email_cc;
    	}
    	
    	if (empty($email_from)) {
    		$email_from = $this->email_from;
    	}
    	
    	if (empty($from_name)) {
    		$from_name = $this->email_from_name;
    	}
    	
    	if (empty($reply_to)) {
    		$reply_to = $this->email_reply_to;
    	}
    	
    	if (empty($attachments)) {
    		$attachments = $this->email_attachments;
    	}

        if (is_array($to)) {
            extract($to);
        }


//        $function_cache_id = false;
//
//        $args = func_get_args();
//
//        foreach ($args as $k => $v) {
//            $function_cache_id = $function_cache_id . serialize($k) . serialize($v);
//        }
//
//        $function_cache_id = __FUNCTION__ . crc32($function_cache_id);
//        $cache_group = 'notifications/email';
//        $cache_content = mw()->cache_manager->get($function_cache_id, $cache_group);
//
//        if ($no_cache == false and ($cache_content) != false) {
//
//            // return $cache_content;
//        }

        $email_from = $email_from ?: mw()->option_manager->get('email_from', 'email');

        if ($email_from == false or $email_from == '') {
        } elseif (!filter_var($email_from, FILTER_VALIDATE_EMAIL)) {
        }

        if ($add_hostname_to_subject != false) {
            $subject = '[' . mw()->url_manager->hostname() . '] ' . $subject;
        }

        if (isset($to) and (filter_var($to, FILTER_VALIDATE_EMAIL))) {
        	$sender =  $this->exec_send($to, $subject, $message, $email_from, $from_name, $reply_to, $attachments);
            if (isset($cc) and ($cc) != false and (filter_var($cc, FILTER_VALIDATE_EMAIL))) {
            	$sender = $this->exec_send($cc, $subject, $message, $email_from, $from_name, $reply_to, $attachments);
            }

           // mw()->cache_manager->save(true, $function_cache_id, $cache_group, 30);

            return $sender;
        } else {
            return false;
        }
    }

    public function test($params)
    {
        $is_admin = is_admin();
        if ($is_admin == false) {
            return array('error' => 'Error: not logged in as admin.' . __FILE__ . __LINE__);
        }

        $email_from = mw()->option_manager->get('email_from', 'email');
        if ($email_from == false or $email_from == '') {
            return array('error' => 'Sender E-mail is not set');
        } elseif (!filter_var($email_from, FILTER_VALIDATE_EMAIL)) {
            return array('error' => 'Sender E-mail is not valid');
        }
        if (isset($params['to']) and (filter_var($params['to'], FILTER_VALIDATE_EMAIL))) {
            $to = $params['to'];
            $subject = 'Test mail';

            if (isset($params['subject'])) {
                $subject = $params['subject'];
            }

            $message = 'Hello! This is a simple email message.';

            $send = $this->exec_send($to, $subject, $message);
            if ($send) {
                return array('success' => $send);
            } else {
                return array('error' => 'Email is not sent');

            }
        }

        return true;
    }

    public function setCc($to)
    {
        $this->cc = $to;
        $this->email_cc = $to;
    }

    public function exec_send($to, $subject, $text, $from_address = false, $from_name = false, $reply_to = false, $attachments = array())
    {
        $from_address = $from_address ?: $this->email_from;
        $from_name = $from_name ?: $this->email_from_name;
        $text = mw()->url_manager->replace_site_url_back($text);

        $content = array();
        $content['content'] = $text;
        $content['subject'] = $subject;
        $content['to'] = $to;
        $content['from'] = $from_address;
        $content['from_name'] = $from_name;
        
      //  $reply_to = mw()->option_manager->get('email_reply', 'contact_form_default');

        ///  escapeshellcmd() has been disabled for security reasons


       try {
              \Mail::send(
                'mw_email_send::emails.simple',
                $content,
              	function ($message) use ($to, $subject, $from_address, $from_name, $reply_to, $attachments) {

                    $from_name = $from_name ?: $from_address;
                    if ($from_address != false) {
                        $message->from($from_address, $from_name);
                    }
                    if ($reply_to != false) {
                        if (is_string($reply_to) and (filter_var($reply_to, FILTER_VALIDATE_EMAIL))) {
                            $message->replyTo($reply_to);
                        }
                    }
                    $message->to($to)->subject($subject);
                    
                    if (is_array($attachments) && !empty($attachments)) {
                    	foreach($attachments as $attachmentFile) {
                    		$message->attach($attachmentFile);
                    	}
                    }
                    
                    return true;
                }
            );
           return true;
         } catch (\Exception $e) {
            if ($this->silent_exceptions) {
               return false;
            } else {
               echo 'Caught exception: ', $e->getMessage(), "\n";
               echo 'File: ', $e->getFile(), "\n";
               echo 'Line: ', $e->getLine(), "\n";
               return false;
            }
         }


        return false;


    }
}
