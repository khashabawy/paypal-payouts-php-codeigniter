<?php 

/**
* paypal payouts example for php
* if it makes things easier for you can buy me a coffee @ paypal > mohandez@hotmail.com
*
* @package            PHP
* @subpackage        Libraries
* @category        Libraries
* @author            AbdAllah Khashaba
* @link            https://khashabawy.com
*/

class Payouts extends CI_Controller {

    public function index(){

        /// PayPal Data
        $mode = "live";  // change

        $paypal_app = array(
            "mode" => "sandbox",
            "sandbox"=> array(
                "client_id"=>"xx", // change
                "secret"=>"yy",  // change
                "endpoints"=>array(
                    "oauth2" => "https://api.sandbox.paypal.com/v1/oauth2/token",
                    "payout" => "https://api.sandbox.paypal.com/v1/payments/payouts",
                )
            ),
            "live"=> array(
                "client_id"=>"xx",  // change
                "secret"=>"yy",  // change
                "endpoints"=>array(
                    "oauth2" => "https://api.paypal.com/v1/oauth2/token",
                    "payout" => "https://api.paypal.com/v1/payments/payouts",
                )
            )            
        );


        $client_id = $paypal_app[$mode]["client_id"];
        $secret = $paypal_app[$mode]["secret"];
        $endpoints = $paypal_app[$mode]["endpoints"];

        ////// PayOut data                
        $PO_id = time(); // change
        $PO_amount = 1.00; // change

        $batch = array(
            "sender_batch_header" => array(
                "sender_batch_id" => $PO_id,
                "email_subject" => "You have a payout!",
                "email_message" => "You have received a payout! Thanks for using our service!",
            ),
            "items" => array(
                0 => array(
                    "recipient_type" => "EMAIL",
                    "amount" => array(
                        "value" => $PO_amount,
                        "currency" => "USD",
                    ),
                    "note"=> "Thanks for your patronage!",
                    "sender_item_id"=> "201403140001",
                    "receiver"=> "hello@world.com",
                )
            )
        );

        $batch_data = json_encode($batch);        


        /// Starting OAuth 
        $this->load->library("curl");        
        $endpoint = $endpoints["oauth2"];
        $this->curl->create($endpoint);
        $this->curl->ssl(FALSE);        
        $this->curl->post("grant_type=client_credentials");
        $this->curl->http_header("Accept","application/json");
        $this->curl->http_header("Accept-Language","en_US");
        $this->curl->http_login($client_id,$secret,"client_credentials");
        $returned = $this->curl->execute();        

        //$this->curl->debug();        

        unset($this->curl);

        $result = json_decode($returned); 

        ///// getting Access Token              

        $nonce = $result->nonce;
        $access_token = $result->access_token;
        $token_type = $result->token_type;
        $app_id = $result->app_id;
        $expires_in = $result->expires_in;


        ///// PayOut Processing
        $this->load->library("curl");        
        $endpoint = $endpoints["payout"];        
        $this->curl->create($endpoint);
        $this->curl->ssl(FALSE);        
        $this->curl->http_header("Content-Type","application/json");
        $this->curl->http_header("Authorization","Bearer $access_token");        
        $this->curl->post($batch_data);
        $this->curl->http_login($client_id,$secret,"client_credentials");
        $returned = $this->curl->execute();        

        //$this->curl->debug();        

        unset($this->curl);

        $result = json_decode($returned);

        if($result && $result->batch_header->batch_status == "PENDING" ){

            $links = $result->links;
            $link = $links[0];

            $endpoint = $link->href;

            $this->load->library("curl");        
            $this->curl->create($endpoint);
            $this->curl->ssl(FALSE);        
            $this->curl->http_header("Content-Type","application/json");
            $this->curl->http_header("Authorization","Bearer $access_token");                    
            $returned = $this->curl->execute();                    

            $result = json_decode($returned);

        }

        echo "<pre>";        
        print_r($result);
        echo "</pre>";        

    }

}
