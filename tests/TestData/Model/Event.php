<?php

namespace Microsoft\Graph\Test\TestData\Model;

use Exception;
use Microsoft\Graph\Core\Models\Date;
use Microsoft\Graph\Core\Models\TimeOfDay;

class Event extends Entity {

    /**
     * @throws Exception
     */
    public function getStartTime(): TimeOfDay {
        if (!is_a($this->_propDict['startTime'], TimeOfDay::class)) {
            return new TimeOfDay($this->_propDict['startTime']);
        }
        return $this->_propDict['startTime'];
    }

    /**
     * @throws Exception
     */
    public function getEventDate(): Date {
        if (!is_a($this->_propDict['eventDate'], Date::class)) {
            return new Date($this->_propDict['eventDate']);
        }
        return $this->_propDict['eventDate'];
    }
}
