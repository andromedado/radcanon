<?php

/**
 * Authorize.net CIM Profile
 * 
 * @version 1.0
 * @author Shad Downey
 * @package RadCanon.AuthNet
 */
class AuthNetCimProfile extends AuthNetCim
{
	
	/**
	 * @param String $id Unique Id to create profile for
	 * @return String Customer Profile ID
	 */
	public function createWithCustomerId ($id)
	{
		$info = array('profile' => array('merchantCustomerId' => $this->formatId($id)));
		$R = $this->getAuthNetXMLRequest()->getAuthNetXMLResponse('createCustomerProfileRequest', $info);
		if (!$R->isGood) {
			throw new ExceptionAuthNet($this->getPublicError($R->code));
		}
		return strval($R->XML->customerProfileId);
	}
	
	/**
	 * @param String $id Unique Id to create profile for
	 * @return String Customer Profile ID
	 */
	public function createWithCustomerIdAndEmail ($id, $email)
	{
		$info = array('profile' => array('merchantCustomerId' => $this->formatId($id), 'email' => $email));
		$R = $this->getAuthNetXMLRequest()->getAuthNetXMLResponse('createCustomerProfileRequest', $info);
		if (!$R->isGood) {
			throw new ExceptionAuthNet($this->getPublicError($R->code));
		}
		return strval($R->XML->customerProfileId);
	}
	
	/**
	 * Take the Given Id and format it for
	 * use as `merchantCustomerId` with Authorize.net
	 * 
	 * @param int $d The Id
	 * @return string Formatted Id
	 */
	public function formatId($id)
	{
		$fID = 'CID-' . UtilsNumber::toLength($id, 5);
		return $fID;
	}
	
	/**
	 * Take the Given Formatted Id and
	 * translate it back into an integer User Id
	 * 
	 * @param string $formattedId
	 * @return int Id
	 */
	public function unFormatId($formattedId)
	{
		$ID = preg_replace('/^CID-0*/i', '', $formattedId);
		return $ID;
	}
	
}

