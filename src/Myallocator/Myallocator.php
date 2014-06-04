<?php namespace 'Myallocator';

class Myallocator {

	public const DEFAULT_TIMEOUT = 600;
	public const DEFAULT_CONNECT_TIMEOUT = 30;

	public const USER_AGENT = 'MyAllocator-PHP/0.1';

    protected $userId = 0;

    protected $userPassword = "";

    protected $vendorId = 0;

    protected $vendorPassword = "";

    protected $root = 'http://api.myallocator.com/';

    protected $channels = array();

    protected $debug = false;

    /**
     * Curl handler.
     * @var mixed
     * @access protected
     */
    protected $ch = null;

    public function __construct($userId, $userPassword, $vendorId, $vendorPassword, $channels = array(), $options = array())
    {
    	$values = $this->loadConfiguration();
    	
    	if(!$userId) $userId = getenv('MYALLOCATOR_USERID');
    	if(!$userId) $userId = (isset($values['user_id']) ? $values['user_id'] : false);
    	if(!$userId) throw new Myallocator_error('You must provide a MyAllocator API User ID');

    	if(!$userPassword) $userPassword = getenv('MYALLOCATOR_USERPASSWORD');
    	if(!$userPassword) $userPassword = (isset($values['user_password']) ? $values['user_password'] : false);
    	if(!$userPassword) throw new Myallocator_error('You must provide a MyAllocator API User Password');

    	if(!$vendorId) $vendorId = getenv('MYALLOCATOR_VENDORID');
    	if(!$vendorId) $vendorId = (isset($values['vendor_id']) ? $values['vendor_id'] : false);
    	if(!$vendorId) throw new Myallocator_error('You must provide a MyAllocator API Vendor ID');

    	if(!$vendorPassword) $vendorPassword = getenv('MYALLOCATOR_USERPASSWORD');
    	if(!$vendorPassword) $vendorPassword = (isset($values['vendor_password']) ? $values['vendor_password'] : false);
    	if(!$vendorPassword) throw new Myallocator_error('You must provide a MyAllocator API Vendor Password');

    	$this->channels = $channels;

    	if (!isset($options['timeout']) || !is_int($options['timeout'])){
            $options['timeout'] = DEFAULT_TIMEOUT;
        }
        if (isset($options['debug'])){
            $this->debug = true;
        }

    	$this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_USERAGENT, USER_AGENT);
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, DEFAULT_CONNECT_TIMEOUT);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $options['timeout']);
    }

    public function __destruct() {
        curl_close($this->ch);
    }

    public function loadConfiguration() {
        $paths = array('~/.myallocator/myallocator.conf', '/etc/myallocator/myallocator.conf');
        foreach($paths as $path) {
            if(file_exists($path)) {
            	// $path must be an PHP file that returns an array
                $values = file_get_contents($path);
                if (is_array($values))
                	return $values;
            }
        }
        return array();
    }

    public function log($message) {
        if($this->debug) error_log($message);
    }

    public function getProperties() {
		$req = <<<EOL
<?xml version="1.0" encoding="UTF-8"?>
<GetProperties>
  <Auth>
    <UserId>$this->user_id</UserId>
    <UserPassword>$this->user_password</UserPassword>
    <VendorId>$this->vendor_id</VendorId>
    <VendorPassword>$this->vendor_password</VendorPassword>
  </Auth>
</GetProperties>
EOL;
		$this->LOGGER->debug('MYALLOCATOR GetProperties request');
		$this->LOGGER->debug(var_export($req, TRUE));
		$this->curl->option(CURLOPT_HTTPHEADER, array('Expect:'));
		$this->curl->option(CURLOPT_RETURNTRANSFER, 1);
		$xml = $this->curl
				->simple_post($this->endpoint_uri,
						array('xmlRequestString' => $req),
						array(CURLOPT_BUFFERSIZE => 10));
		$this->LOGGER->debug('MYALLOCATOR GetProperties response');
		$this->LOGGER->debug(var_export($xml, TRUE));
		$properties = array();
		$entities = simplexml_load_string($xml);
		foreach ($entities->Properties->Property as $property) {
			$p = new stdClass();
			$p->id = (string) $property->Id;
			$p->breakfast = (string) $property->Breakfast;
			$p->country = (string) $property->Country;
			$p->currency = (string) $property->Currency;
			$p->name = (string) $property->Name;
			$p->paid_until = (string) $property->PaidUntil;
			$p->weekend = array();
			$w = $property->Weekend;
			foreach ($w->Day as $day) {
				$p->weekend[] = (string) $day;
			}
			$properties[] = $p;
		}
		return $properties;
	}

	public function getRoomTypes($propertyId) {
		$req = <<<EOL
<?xml version="1.0" encoding="UTF-8"?>
<GetRoomTypes>
  <Auth>
    <UserId>$this->user_id</UserId>
    <UserPassword>$this->user_password</UserPassword>
	<PropertyId>$property_id</PropertyId>
    <VendorId>$this->vendor_id</VendorId>
    <VendorPassword>$this->vendor_password</VendorPassword>
  </Auth>
</GetRoomTypes>
EOL;
		$this->LOGGER->debug('MYALLOCATOR GetRoomTypes request');
		$this->LOGGER->debug(var_export($req, TRUE));
		$this->curl->option(CURLOPT_HTTPHEADER, array('Expect:'));
		$this->curl->option(CURLOPT_RETURNTRANSFER, 1);
		$xml = $this->curl
				->simple_post($this->endpoint_uri,
						array('xmlRequestString' => $req),
						array(CURLOPT_BUFFERSIZE => 10));
		$this->LOGGER->debug('MYALLOCATOR GetRoomTypes response');
		$this->LOGGER->debug(var_export($xml, TRUE));
		$room_types = array();
		$entities = simplexml_load_string($xml);
		foreach ($entities->RoomTypes->RoomType as $room_type) {
			$rt = new stdClass();
			$rt->id = (string) $room_type->Id;
			$rt->label = (string) $room_type->Label;
			$rt->units = (string) $room_type->Units;
			$rt->occupancy = (string) $room_type->Occupancy;
			$rt->beds = (string) $room_type->Beds;
			$rt->gender = (string) $room_type->Gender;
			$rt->double_bed = (string) $room_type->DoubleBed;
			$rt->ensuite = (string) $room_type->Ensuite;
			$rt->private_room = (string) $room_type->PrivateRoom;
			$room_types[] = $rt;
		}
		return $room_types;
	}

	public function getBookings($propertyId, $from, $to) {
		$req = <<<EOL
<?xml version="1.0" encoding="UTF-8"?>
<GetBookings>
  <Auth>
    <UserId>$this->user_id</UserId>
    <UserPassword>$this->user_password</UserPassword>
	<PropertyId>$property_id</PropertyId>
    <VendorId>$this->vendor_id</VendorId>
    <VendorPassword>$this->vendor_password</VendorPassword>
  </Auth>

  <ArrivalStartDate>$start_date</ArrivalStartDate>
  <ArrivalEndDate>$end_date</ArrivalEndDate>

</GetBookings>
EOL;
		$this->LOGGER->debug('MYALLOCATOR GetBookings request');
		$this->LOGGER->debug(var_export($req, TRUE));
		$this->curl->option(CURLOPT_HTTPHEADER, array('Expect:'));
		$this->curl->option(CURLOPT_RETURNTRANSFER, 1);
		$xml = $this->curl
				->simple_post($this->endpoint_uri,
						array('xmlRequestString' => $req),
						array(CURLOPT_BUFFERSIZE => 10));
		$this->LOGGER->debug('MYALLOCATOR GetBookings response');
		$this->LOGGER->debug(var_export($xml, TRUE));
		$bookings = array();
		$entities = simplexml_load_string($xml);
		if (isset($entities) && $entities) {
			foreach ($entities->Bookings->Booking as $booking) {
				$b = new MA_Booking();
				$b->setChannel($booking->Channel);
				$b->setStartDate($booking->StartDate);
				$b->setEndDate($booking->EndDate);
				$b->setIsCancelation($booking->IsCancellation);
				$b->setMyAllocatorId($booking->MyallocatorId);
				$b->setMyAllocatorCreationDate($booking->MyallocatorCreationDate);
				$b->setMyAllocatorCreationTime($booking->MyallocatorCreationTime);
				$b->setMyAllocatorModificationDate($booking->MyallocatorModificationDate);
				$b->setMyAllocatorModificationTime($booking->MyallocatorModificationTime);
				$b->setOrderId($booking->OrderId);
				$b->setOrderDate($booking->OrderDate);
				$b->setOrderTime($booking->OrderTime);
				$b->setOrderSource($booking->OrderSource);
				$b->setOrderAdults($booking->OrderAdults);
				$b->setOrderChildren($booking->OrderChildren);
				$b->setDeposit($booking->Deposit);
				$b->setTotalPrice($booking->TotalPrice);
				$b->setTotalCurrency($booking->TotalCurrency);
				foreach ($entities->Bookings->Booking->Customers->Customer as $customer) {
					$c = new MA_Customer();
					$c->setCustomerFName($customer->CustomerFName);
					$c->setCustomerLName($customer->CustomerLName);
					$c->setCustomerAddress($customer->CustomerAddress);
					$c->setCustomerArrivalTime($customer->CustomerArrivalTime);
					$c->setCustomerEmail($customer->CustomerEmail);
					$c->setCustomerNationality($customer->CustomerNationality);
					$c->setCustomerPhone($customer->CustomerPhone);
					$c->setCustomerCompany($customer->CustomerCompany);
					$c->setCustomerCity($customer->CustomerCity);
					$c->setCustomerState($customer->CustomerState);
					$c->setCustomerPostCode($customer->CustomerPostCode);
					$c->setCustomerCountry($customer->CustomerCountry);
					$c->setCustomerNote($customer->CustomerNote);
					$b->addCustomer($c);
				}
				foreach ($entities->Bookings->Booking->Rooms->Room as $room) {
					$r = new MA_Room();
					$r->setStartDate($room->StartDate);
					$r->setEndDate($room->EndDate);
					$r->setStartDate($room->Price);
					$r->setStartDate($room->Currency);
					foreach ($entities->Bookings->Booking->Rooms->Room->RoomTypeIds as $room_type_id) {
						$r->addRoomTypeId($room_type_id->RoomTypeId);
					}
					$r->setStartDate($room->RoomDesc);
					$r->setStartDate($room->Units);
					$b->addRoom($r);
				}
				$bookings[] = $b;
			}
		}
		return $bookings;
	}

	/**
	 * @param number $property_id
	 * @param array|MA_Allocation $allocations
	 */
	public function setAllocation($propertyId, $allocations = array()) {
		if (!isset($property_id) || !$property_id || $property_id <= 0) {
			throw new InvalidArgumentException(sprintf('Invalid property ID: %s', (string) $property_id));
		}
		if (!isset($allocations) || !is_array($allocations) || empty($allocations)) {
			throw new InvalidArgumentException('Invalid allocations.');
		}

		$req = $this->get_set_allocation_xml($property_id, $allocations);

		$this->LOGGER->info('MYALLOCATOR SetAllocation request');
		$this->LOGGER->info(var_export($req, TRUE));
		$this->curl->option(CURLOPT_HTTPHEADER, array('Expect:'));
		$this->curl->option(CURLOPT_RETURNTRANSFER, 1);
		$xml_response = $this->curl
				->simple_post($this->endpoint_uri,
						array('xmlRequestString' => $req),
						array(CURLOPT_BUFFERSIZE => 10));
		$this->LOGGER->info('MYALLOCATOR SetAllocation response');
		$this->LOGGER->info(var_export($xml_response, TRUE));
		$entities = simplexml_load_string($xml_response);
		if (isset($entities) && $entities) {
		    $success = $entities->Success;
		    $success = ($success == 'true');

		    if (! $success) {
		        foreach ($entities->Errors->Error as $error) {
		            $error_id = (string) $error->ErrorId;
		            $error_message = (string) $error->ErrorMsg;
		            log_message('info', 'MYALLOCATOR ERROR ['.$error_id.']: ' . $error_message);
		            log_message('info', $req);
		        }
		    } else {
		        log_message('info', 'MYALLOCATOR SUCCESSFULLY SYNCED!');
		        return $success;
		    }
		}
		return false;
	}
	// @codeCoverageIgnoreEnd
	protected function getSetAllocationXML($property_id, $allocations = array()) {
		$channels_str = "\t\t<Channel>all</Channel>\n";
		if (!empty($this->channels)) {
			$channels_str = '';
			foreach ($this->channels as $channel) {
				$channels_str = $channels_str . "\t\t<Channel>$channel</Channel>\n";
			}
		}
		$allocations_str = "";
		foreach ($allocations as $allocation) {
			$xml = "";
			$xml = $xml . "\t\t<Allocation>\n";
			$xml = $xml
			. "\t\t\t<RoomTypeId>".$allocation->get_room_type_id()."</RoomTypeId>\n";
			$xml = $xml
			. "\t\t\t<StartDate>".$allocation->get_start_date()->format('Y-m-d')."</StartDate>\n";
			$xml = $xml . "\t\t\t<EndDate>".$allocation->get_end_date()->format('Y-m-d')."</EndDate>\n";
			$xml = $xml . "\t\t\t<Units>".$allocation->get_units()."</Units>\n";
			$prices = $allocation->get_prices();
			if (!empty($prices)) {
				$xml = $xml . "\t\t\t<Prices>\n";
				foreach ($prices as $price) {
					if ($price->is_weekend())
						$xml = $xml . sprintf("\t\t\t\t<Price weekend='true'>%.2f</Price>\n", $price->get_price());
					else
						$xml = $xml
						. sprintf("\t\t\t\t<Price>%.2f</Price>\n", $price->get_price());
				}
				$xml = $xml . "\t\t\t</Prices>\n";
			}
			$xml = $xml . "\t\t</Allocation>\n";
			$allocations_str = $allocations_str . $xml;
		}
		$req =	"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
				"<SetAllocation>\n" .
  					"\t<Auth>\n" .
    					"\t\t<UserId>{$this->user_id}</UserId>\n" .
    					"\t\t<UserPassword>{$this->user_password}</UserPassword>\n" .
    					"\t\t<PropertyId>{$property_id}</PropertyId>\n" .
    					"\t\t<VendorId>{$this->vendor_id}</VendorId>\n" .
    					"\t\t<VendorPassword>{$this->vendor_password}</VendorPassword>\n" .
  					"\t</Auth>\n" .
  					"\t<Channels>\n" .
    				$channels_str .
  					"\t</Channels>\n" .
  					"\t<Allocations>\n" .
    				$allocations_str .
  					"\t</Allocations>\n" .
				"</SetAllocation>\n";
		return $req;
	}

}
