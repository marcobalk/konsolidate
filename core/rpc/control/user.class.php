<?php

	/**
	 *            ________ ___        
	 *           /   /   /\  /\       Konsolidate
	 *      ____/   /___/  \/  \      
	 *     /           /\      /      http://konsolidate.klof.net
	 *    /___     ___/  \    /       
	 *    \  /   /\   \  /    \       Class:  CoreRPCControlUser
	 *     \/___/  \___\/      \      Tier:   Core
	 *      \   \  /\   \  /\  /      Module: RPC/Control/User
	 *       \___\/  \___\/  \/       
	 *         \          \  /        $Rev: 49 $
	 *          \___    ___\/         $Author: rogier $
	 *              \   \  /          $Date: 2007-07-09 10:44:23 +0200 (Mon, 09 Jul 2007) $
	 *               \___\/           
	 */
	class CoreRPCControlUser extends Konsolidate implements CoreRPCControlInterface
	{
		private $_request;
		private $_message;
		private $_content;
		private $_status;


		/*  Interface requirements  */
		public function getMessage()
		{
			return isset( $this->_message ) ? $this->_message : null;
		}

		public function getContent()
		{
			return isset( $this->_content ) ? $this->_content : null;
		}

		public function getStatus()
		{
			return isset( $this->_status ) ? (bool) $this->_status : false;
		}


		/*  Controls  */
		private function loadRequest()
		{
			if ( !isset( $this->_request ) )
				$this->_request = &$this->register( "/Request" );
		}

		public function login()
		{
			$this->loadRequest();
			$sResult = $this->call( "/User/login", $this->_request->email, $this->_request->password );
			if ( is_string( $sResult ) )
			{
				$this->_status  = true;
				$this->_message = $this->call( "/Language/translate", "You've been signed on" );
				$this->_content = Array(
					"redirect"=>"/home.php"
				);
				return true;
			}
			$this->_message = $this->call( "/Language/translate", "Invalid username/password combination" );
			return false;
		}

		public function create()
		{
			$this->loadRequest();
			$aError = Array();

			$sEmail           = trim( $this->_request->email );
			$sPassword        = trim( $this->_request->password );
			$sPasswordConfirm = trim( $this->_request->passwordconfirm );
			$sAgree           = trim( $this->_request->agree );
			$sOptIn           = trim( $this->_request->optin );
			$sTrack           = trim( $this->_request->track );

			if ( !$this->call( "/Validate/isEmail", $sEmail ) )
				array_push( $aError, Array(
					"fieldname"=>"email",
					"message"=>"'{$this->_request->email}' is not a valid email address"
				) );

			//  Validate password and match passwordconfirm to it (must be equal)
			if ( !$this->call( "/Validate/isFilled", $sPassword ) )
				array_push( $aError, Array( 
					"fieldname"=>"passwordconfirm", 
					"message"=>"you need to fill in a password"
				) );
			else if ( $sPassword != $sPasswordConfirm )
				array_push( $aError, Array( 
					"fieldname"=>"passwordconfirm", 
					"message"=>"passwords do not match"
				) );

			//  agreed?
			if ( (bool) $sAgree === false )
				array_push( $aError, Array( 
					"fieldname"=>"agree", 
					"message"=>"you must agree to our terms"
				) );

			//  optin?
			if ( (bool) $sOptIn === false )
				array_push( $aError, Array( 
					"fieldname"=>"optin", 
					"message"=>"we need to be able to send you e-mail"
				) );

			//  track?
			if ( (bool) $sTrack === false )
				array_push( $aError, Array( 
					"fieldname"=>"track", 
					"message"=>"we will track your movements anyway, so you must agree"
				) );

			if ( count( $aError ) == 0 )
			{
				if ( $this->get( "/User/registered" ) )
				{
					$bError   = true;
					$sMessage = "You already seem to have an account on this website. Please log in using that account.";
				}
				else
				{
					if ( $this->call( "/User/create", $sEmail, $sPassword, (bool) $sAgree, (bool) $sOptIn, (bool) $sTrack ) )
					{
						$this->_status  = true;
						$this->_message = "Your user account was created succesfully. You will be redirected to your 'final destination' in a moment.";
						$this->_content = Array( "redirect"=>"home.php" );
						return true;
					}
					else
					{
						$this->_status  = false;
						$this->_message = "An error occured when trying to create your user account, please try again later.";
					}
				}
			}
			else
			{
				$this->_status  = true;
				$this->_message = "Some fields contained errors, these fields are marked red. Feel free to correct them and try again.";
				$this->_content = Array(
					"error"=>$aError
				);
			}
			return false;
		}
	}

?>