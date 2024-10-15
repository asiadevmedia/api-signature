<?php

namespace Asiadevmedia\ApiSignature;

class Signature {
    private $timeout_ofset = 0;
    private $timeout_limit = 0;
    
    private $signatureNonce = '';
    private $timestamp = '';
    private $signature = '';
    private $secret = '';

    public function __construct($signatureNonce = '', $secret = '', $timestamp = '', $signature = '')
    {

        $this->timeout_ofset = get_option('timeout_ofsets');
        $this->timeout_limit = get_option('timeout_limits');
        var_dump($this->timeout_ofset);
        var_dump($this->timeout_limit);
        if (empty($secret)) {
            echo wp_json_encode(
                array(
                    'loggedin' => false,
                    'message' => esc_html('secret could not be empty', 'security-login')
                )
            );
            die();
        } else if(empty($timestamp)){
            echo wp_json_encode(
                array(
                    'loggedin' => false,
                    'message' => esc_html('timestamp could not be empty', 'security-login')
                )
            );
            die();
        } else if(empty($signatureNonce)){
            echo wp_json_encode(
                array(
                    'loggedin' => false,
                    'message' => esc_html('signatureNonce could not be empty', 'security-login')
                )
            );
            die();
        }

        $this->signatureNonce = $signatureNonce;
        $this->secret = $secret;
        $this->timestamp = $timestamp;
        $this->signature = $signature;
    }

    public function generate()
    {
        if ((int)$this->timeout_limit !== 0 && !isset($this->timestamp)) {
            $this->timestamp = time();
        }

        $outer = sprintf("%s%s%d", $this->signatureNonce, $this->secret, $this->timestamp);
        $sign = md5($outer);

        return strtolower(strval($sign));
    }

    /**
     * @return bool
     * @throws ApiSignatureException
     */
    public function verify(): bool
    {
        if ( (int) $this->timeout_limit !== 0) {
            $times = abs(time() - $this->timestamp ?? 0);
            if ($times < $this->timeout_limit) {
                $timesd = abs($times - $this->timeout_limit ?? 0);
                echo wp_json_encode(
                    array(
                        'loggedin' => false,
                        'timestamp' => $timesd,
                        'message' => esc_html('Please wait..,and try again!', 'security-login')
                    )
                );
                die();
            }
            else if ($times > $this->timeout_ofset){
                echo wp_json_encode(
                    array(
                        'loggedin' => false,
                        'message' => esc_html('Signature timeout, please refresh pages!', 'security-login')
                    )
                );
                die();
            }
        }

        $signcompare = $this->generate();
        
        if ($this->signature !== $signcompare) {
            echo wp_json_encode(
                array(
                    'loggedin' => false,
                    'message' => esc_html('Signature verify failed', 'security-login')
                )
            );
            die();
        }
        
        return true;
    }
}