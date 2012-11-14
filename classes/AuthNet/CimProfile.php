<?php

/**
 * Authorize.net CIM Profile
 * 
 * @version 1.1
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
		return $this->createWithProfileInfo(array('merchantCustomerId' => $this->formatId($id)));
	}
	
	/**
	 * @param String $id Unique Id to create profile for
	 * @return String Customer Profile ID
	 */
	public function createWithCustomerIdAndEmail ($id, $email)
	{
		return $this->createWithProfileInfo(array('merchantCustomerId' => $this->formatId($id), 'email' => $email));
	}
	
	/**
	 * Using the given profile information, create a CIM Profile
	 * You must use at least one of: [merchantCustomerId, email, description]
	 * @throws ExceptionAuthNet
	 * @param Array $info array('merchantCustomerId' => #)
	 * @return String Customer Profile ID
	 */
	public function createWithProfileInfo(array $info)
	{
		if (empty($info['merchantCustomerId']) && empty($info['email']) && empty($info['description'])) {
			throw new ExceptionAuthNet('You must use at least one of: [merchantCustomerId, email, description]');
		}
		$R = $this->getAuthNetXMLRequest()->getAuthNetXMLResponse('createCustomerProfileRequest', array('profile' => $info));
		if (!$R->isGood) {
			throw new ExceptionAuthNet($this->getPublicError($R->code));
		}
		return strval($R->XML->customerProfileId);
	}
	
	/**
	 * Delete the given Payment Profile attached to this customer profile
	 * @throws ExceptionAuthNet
	 * @param Integer $customerPaymentProfileId
	 * @return true
	 */
	public function deleteCustomerPaymentProfile($customerPaymentProfileId)
	{
		$R = $this->getAuthNetXMLRequest()->getAuthNetXMLResponse('deleteCustomerPaymentProfileRequest', array('customerProfileId' => $this->id, 'customerPaymentProfileId' => $customerPaymentProfileId));
		if (!$R->isGood) {
			throw new ExceptionAuthNet($this->getPublicError($R->code));
		}
		return true;
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

