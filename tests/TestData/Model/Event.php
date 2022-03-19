<?php

namespace Microsoft\Graph\Core\Test\TestData\Model;

use Exception;
use Microsoft\Kiota\Abstractions\Types\Date;
use Microsoft\Kiota\Abstractions\Types\Time;

class Event extends Entity {

    /**
     * @throws Exception
     */
    public function getStartTime(): Time {
        if (!is_a($this->_propDict['startTime'], Time::class)) {
            return new Time($this->_propDict['startTime']);
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

    public function setEventDate(Date $date): void
    {
        $this->_propDict['eventDate'] = $date;
    }

    public function setStartTime(Time $timeOfDay): void {
        $this->_propDict['startTime'] = $timeOfDay;
    }
}
