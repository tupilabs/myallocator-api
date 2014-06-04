<?php namespace 'Myallocator';

class Myallocator_Allocation {
	/**
	 * Room type ID.
	 * @var number
	 * @access private
	 */
	private $roomTypeId;
	/**
	 * From.
	 * @var DateTime
	 * @access private
	 */
	private $startDate;
	/**
	 * To.
	 * @var DateTime
	 * @access private
	 */
	private $endDate;
	/**
	 * Number of units.
	 * @var number
	 * @access private
	 */
	private $units;
	/**
	 * Array of prices.
	 * @var array|Myallocator_Price
	 * @access private
	 */
	private $prices = array();

	public function getRoomTypeId() {
		return $this->roomTypeId;
	}

	public function setRoomTypeId($roomTypeId) {
		$this->roomTypeId = $roomTypeId;
	}

	public function getStartDate() {
		return $this->startDate;
	}

	public function setStartDate($startDate) {
		$this->startDate = $startDate;
	}

	public function getEndDate() {
		return $this->endDate;
	}

	public function setEndDate($endDate) {
		$this->endDate = $endDate;
	}

	public function getUnits() {
		return $this->units;
	}

	public function setUnits($units) {
		$this->units = $units;
	}

	/**
	 * @param Myallocator_Price $price
	 */
	public function addPrice($price) {
		foreach ($this->prices as $existing_price) {
			if ($existing_price->price === $price->price && $existing_price->weekend === $price->weekend)
				return;
		}
		$this->prices[] = $price;
	}

	public function get_prices() {
		return $this->prices;
	}

	/**
	 * Adds a date to the allocation.
	 * @param Carbon $date
	 */
	public function add_date($date) {
		if (!isset($date) || is_null($date) || ! $date instanceof Carbon)
			throw new Myallocator_error('Invalid allocation date: ' $date);

		// Nothing set yet
		if (!isset($this->startDate)) {
			$this->start_date = $date;
			$this->end_date = $date;
		}

		// Check if it is within our period range
		$interval = \DateInterval::createFromDateString('1 day');
		$period = new \DatePeriod($this->start_date, $interval, $this->end_date);
		foreach ($period as $a_date) {
			$period_date =  \Carbon\Carbon::instance($a_date);
			if ($period_date->eq($date))
				return TRUE;
		}

		// Check if it's the next day in the range
		$copy_of_end_date = $this->end_date->copy();
		if ($copy_of_end_date->addDay()->format('Y-m-d') === $date->format('Y-m-d')) {
			$this->end_date = $date;
			return TRUE;
		}

		return FALSE;
	}

	public function equals(MA_Allocation $allocation) {
		$b = (
			$this->room_type_id === $allocation->room_type_id &&
			$this->units === $allocation ->units
		);
		if ($b !== TRUE)
			return FALSE;
		foreach ($this->prices as $price) {
			$found = FALSE;
			foreach ($allocation->prices as $others_price) {
				if ($price->price === $others_price->price && $price->weekend == $others_price->weekend)
					$found = TRUE;
			}
			if (!$found)
				return FALSE;
		}
		return TRUE;
	}

}
