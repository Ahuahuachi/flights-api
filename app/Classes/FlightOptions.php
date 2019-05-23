<?php

namespace App\Classes;


class FlightOptions
{
    static function isNightly($datetime)
    {
        $timestamp = $datetime->getTimestamp();
        $sunset_time = date_sunset($timestamp, SUNFUNCS_RET_TIMESTAMP);
        $sunrise_time = date_sunrise($timestamp, SUNFUNCS_RET_TIMESTAMP);

        $result = (($timestamp < $sunrise_time) || ($timestamp >= $sunset_time)) ? true : false;

        return $result;
    }

    public static function getFlightOptionsAir(\SimpleXMLElement $airXml)
    {
        // Get flight details list
        $flightDetailsList = $airXml->FlightDetailsList->FlightDetails;

        // Build flight details array
        foreach ($flightDetailsList as $flightDetailsElement) {
            $flightDetailsAttributes = $flightDetailsElement->attributes();
            $flightDetailsKey = strval($flightDetailsAttributes['Key']);

            $flightDetails[$flightDetailsKey] = [
                'Origin' => strval($flightDetailsAttributes['Origin']),
                'Destination' => strval($flightDetailsAttributes['Destination']),
                'DepartureTime' => strval($flightDetailsAttributes['DepartureTime']),
                'ArrivalTime' => strval($flightDetailsAttributes['ArrivalTime']),
                'FlightTime' => strval($flightDetailsAttributes['FlightTime']),
                'Equipment' => strval($flightDetailsAttributes['Equipment']),
                'OriginTerminal' => strval($flightDetailsAttributes['OriginTerminal']),
                'DestinationTerminal' => strval($flightDetailsAttributes['DestinationTerminal']),
            ];
        }

        // Get air segment list
        $airSegmentList = $airXml->AirSegmentList->AirSegment;

        // Build air segments array
        foreach ($airSegmentList as $airSegmentElement) {

            $airSegmentAttributes = $airSegmentElement->attributes();
            $airSegmentKey = trim(strval($airSegmentAttributes['Key']));
            $codeshareInfo = $airSegmentElement->CodeshareInfo;

            if ($codeshareInfo) {
                $codeshareInfoAttributes = $codeshareInfo->attributes();

                if (!$codeshareInfoAttributes['OperatingCarrier'] || !$codeshareInfoAttributes['OperatingFlightNumber']) {
                    $codeshareInfoAttributes = [
                        'OperatingCarrier' => 'N/A',
                        'OperatingFlightNumber' => 'N/A',
                    ];
                }
            }


            // Get flight details ref list
            $flightDetailsRefList = $airSegmentElement->FlightDetailsRef;
            $flightDetailsRefKey = strval($flightDetailsRefList->attributes()['Key']);


            $airSegments[$airSegmentKey] = [
                'Carrier' => strval($airSegmentAttributes['Carrier']),
                'FlightNumber' => strval($airSegmentAttributes['FlightNumber']),
                'Origin' => strval($airSegmentAttributes['Origin']),
                'Destination' => strval($airSegmentAttributes['Destination']),
                'DepartureTime' => strval($airSegmentAttributes['DepartureTime']),
                'ArrivalTime' => strval($airSegmentAttributes['ArrivalTime']),
                'FlightTime' => strval($airSegmentAttributes['FlightTime']),
                'Equipment' => strval($airSegmentAttributes['Equipment']),
                'OperatingCarrier' => strval($codeshareInfoAttributes['OperatingCarrier']),
                'OperatingFlightNumber' => strval($codeshareInfoAttributes['OperatingFlightNumber']),
                'FlightDetails' => $flightDetails[$flightDetailsRefKey],
            ];
        }

        // Get pricing solution list
        $pricingSolutionList = $airXml->AirPricingSolution;

        // Date & time formats
        $sourceDateFormat = 'Y-m-d\TH:i:s.uP';
        $outputDateFormat = 'Y-m-d';
        $outputTimeFormat = 'H:i';

        // Build pricing solution array
        foreach ($pricingSolutionList as $pricingSolutionElement) {

            // Get price
            $pricingSolutionAttributes = $pricingSolutionElement->attributes();
            $totalPrice = strval($pricingSolutionAttributes['TotalPrice']);
            $totalPriceCurrency = substr($totalPrice, 0, 3);
            $totalPriceAmount = floatval(substr($totalPrice, 3));

            // Get booking info list
            $bookingInfoList = $pricingSolutionElement->AirPricingInfo->BookingInfo;

            // Build booking info array
            $bookingInfo = [];
            foreach ($bookingInfoList as $bookingInfoElement) {
                $bookingInfoAttributes = $bookingInfoElement->attributes();
                $bookingCode = strval($bookingInfoAttributes['BookingCode']);
                $bookingCount = intval($bookingInfoAttributes['BookingCount']);
                $cabinClass = strval($bookingInfoAttributes['CabinClass']);
                $fareInfoRef = strval($bookingInfoAttributes['FareInfoRef']);
                $segmentRef = strval($bookingInfoAttributes['SegmentRef']);

                $bookingInfo[$segmentRef] = [
                    'BookingCode' => $bookingCode,
                    'BookingCount' => $bookingCount,
                    'CabinClass' => $cabinClass,
                    'FareInfoRef' => $fareInfoRef,
                ];
            }

            // Get journey list
            $journeyList = $pricingSolutionElement->Journey;

            // Build journey array
            $journeys = [];
            foreach ($journeyList as $journeyElement) {
                $travelTime = $journeyElement->attributes()['TravelTime'];

                // Get air segment ref list
                $airSegmentRefList = $journeyElement->AirSegmentRef;

                // Get air segments for journey
                $journeyAirSegments = [];
                $airlines = [];
                $departureArrivalInfo = [];
                $scaleNumber = 0;
                $i = 0;
                $j = 0;

                foreach ($airSegmentRefList as  $airSegmentRefElement) {

                    // Get the codes from the airlines that operates the segments
                    $airSegmentRefKey = strval($airSegmentRefElement->attributes()['Key']);
                    $airlineCode = $airSegments[$airSegmentRefKey]['Carrier'];

                    $airlines[] = [
                        'code' => $airlineCode,
                    ];


                    // Build journey segments with flights and scales
                    $journeyAirSegment = $airSegments[$airSegmentRefKey];
                    $journeySegmentFlightDetails = $journeyAirSegment['FlightDetails'];
                    $departureAirport = $journeySegmentFlightDetails['Origin'];
                    $departureDateTime = date_create_from_format($sourceDateFormat, $journeySegmentFlightDetails['DepartureTime']);
                    $arrivalDateTime = date_create_from_format($sourceDateFormat, $journeySegmentFlightDetails['ArrivalTime']);
                    $journeySegmentDuration = date_diff($arrivalDateTime, $departureDateTime);
                    $journeySegmentBookingInfo = $bookingInfo[$airSegmentRefKey];

                    // Add scale segments
                    if ($i != 0) {
                        $journeyAirSegments[] = [];
                        $scaleNumber++;
                    }

                    // Build air segments
                    $journeyAirSegments[] = [
                        'type' => 'flight',
                        'departure' => [
                            'airport' => [
                                'code' => $journeySegmentFlightDetails['Origin'],
                                'terminal' => $journeySegmentFlightDetails['OriginTerminal'],
                            ],
                            'date' => $departureDateTime->format($outputDateFormat),
                            'time' => $departureDateTime->format($outputTimeFormat),
                        ],
                        'arrival' => [
                            'airport' => [
                                'code' => $journeySegmentFlightDetails['Destination'],
                                'terminal' => $journeySegmentFlightDetails['DestinationTerminal'],
                            ],
                            'date' => $arrivalDateTime->format($outputDateFormat),
                            'time' => $arrivalDateTime->format($outputTimeFormat),
                        ],
                        'isNightly' => self::isNightly($departureDateTime),
                        'duration' => [
                            'hours' => $journeySegmentDuration->h,
                            'minutes' => $journeySegmentDuration->i,
                        ],
                        'flightNumber' => $journeyAirSegment['FlightNumber'],
                        'aircraft' => $journeyAirSegment['Equipment'],
                        'airline' => [
                            'code' => $journeyAirSegment['Carrier'],
                        ],
                        'operatingAirline' => [
                            'code' => $journeyAirSegment['OperatingCarrier'],
                        ],
                        'class' => [
                            'code' => $journeySegmentBookingInfo['BookingCode'],
                            'type' => $journeySegmentBookingInfo['CabinClass'],
                        ]

                    ];

                    // Build scale segments
                    $departureArrivalInfo[] = [
                        'departureDateTime' => $departureDateTime,
                        'departureTerminal' => $journeySegmentFlightDetails['OriginTerminal'],
                        'arrivalDateTime' => $arrivalDateTime,
                        'arrivalTerminal' => $journeySegmentFlightDetails['DestinationTerminal'],
                    ];

                    if ($i != 0) {
                        $scaleArrivalTerminal = $departureArrivalInfo[$j - 1]['arrivalTerminal'];
                        $scaleDepartureTerminal = $departureArrivalInfo[$j]['departureTerminal'];
                        $scaleArrivalDateTime = $departureArrivalInfo[$j - 1]['arrivalDateTime'];
                        $scaleDepartureDateTime = $departureArrivalInfo[$j]['departureDateTime'];

                        $changeTerminal = ($scaleArrivalTerminal != $scaleDepartureTerminal) ? true : false;
                        $scaleDuration = date_diff($scaleDepartureDateTime, $scaleArrivalDateTime);

                        $journeyAirSegments[$i] = [
                            'type' => 'scale',
                            'changeTerminal' => $changeTerminal,
                            'isNightly' => self::isNightly($scaleArrivalDateTime),
                            'duration' => [
                                'hours' => $scaleDuration->h,
                                'minutes' => $scaleDuration->i,
                            ]
                        ];

                        $i++;
                    }

                    $i++;
                    $j++;
                }


                // Get departure and arrival details
                $departureAirSegment = $journeyAirSegments[0]['departure'];
                $departureAirport = $departureAirSegment['airport']['code'];
                $departureDate = $departureAirSegment['date'];
                $departureTime = $departureAirSegment['time'];

                $departure = [
                    'airport' => ['code' => $departureAirport],
                    'date' => $departureDate,
                    'time' => $departureTime,
                ];

                $arrivalAirSegment = end($journeyAirSegments)['arrival'];
                $arrivalAirport = $arrivalAirSegment['airport']['code'];
                $arrivalDate = $arrivalAirSegment['date'];
                $arrivalTime = $arrivalAirSegment['time'];

                $arrival = [
                    'airport' => ['code' => $arrivalAirport],
                    'date' => $arrivalDate,
                    'time' => $arrivalTime,
                ];

                // Get journey duration
                $journeyDepartureDateTime = $departureArrivalInfo[0]['departureDateTime'];
                $journeyArrivalDateTime = end($departureArrivalInfo)['arrivalDateTime'];
                $travelTime = date_diff($journeyArrivalDateTime, $journeyDepartureDateTime);
                $journeyDuration = [
                    'hours' => $travelTime->h,
                    'minutes' => $travelTime->i,
                ];

                $airlines = array_unique($airlines, SORT_REGULAR);

                $journeys[] = [
                    'journey' => '/* Id del trayecto */',
                    'airlines' => $airlines,
                    'departure' => $departure,
                    'arrival' => $arrival,
                    'duration' => $journeyDuration,
                    'segments' => $journeyAirSegments,
                    'scale' => $scaleNumber,
                ];
            }

            //Build price response
            $responsePrice = [
                'amount' => $totalPriceAmount,
                'currency' => $totalPriceCurrency,
            ];


            // Build flights response
            $responseFlights[] = [
                'journeys' => $journeys,
                'option' => [
                    'price' => $responsePrice,
                ],
            ];
        }


        $response = [
            'count' => count($responseFlights),
            'flights' => $responseFlights,
        ];


        return $response;
    }

    public static function getFlightOptionsSoap(\SimpleXMLElement $soapXml)
    {
        // helper function in case php < 7.3
        if (!function_exists('array_key_first')) {
            function array_key_first(array $arr)
            {
                foreach ($arr as $key => $unused) {
                    return $key;
                }
                return NULL;
            }
        }

        // Get list of pricing groups, itineraries from xml
        $airItineraryList = $soapXml->AirAvailSearchResponse->AirAvailSearchResult->AirAvail->AirItineraries->AirItinerary;

        // DateTime format strings
        $sourceDateTimeFormat = 'd/m/Y H:i';
        $outputDateFormat = 'Y-m-d';
        $outputTimeFormat = 'H:i';

        // Build array of itineraries
        foreach ($airItineraryList as $airItineraryElement) {
            $itineraryId = trim(strval($airItineraryElement->ItineraryID));
            $itineraryDepartureDateTimeString = trim(strval($airItineraryElement->DepartureDateTime));
            $itineraryDepartureDateTime = \DateTime::CreateFromFormat($sourceDateTimeFormat, $itineraryDepartureDateTimeString);
            $itineraryArrivalDateTimeString = trim(strval($airItineraryElement->ArrivalDateTime));
            $itineraryArrivalDateTime = \DateTime::CreateFromFormat($sourceDateTimeFormat, $itineraryArrivalDateTimeString);
            $itineraryArrivalAirportLocationCode = trim(strval($airItineraryElement->ArrivalAirportLocationCode));
            $itineraryDepartureAirportLocationCode = trim(strval($airItineraryElement->DepartureAirportLocationCode));
            $itineraryTotalDuration = \DateTime::CreateFromFormat('H:i', trim(strval($airItineraryElement->TotalDuration)));

            // Get itinerary legs list
            $itineraryLegsList = $airItineraryElement->AirItineraryLegs->AirItineraryLeg;

            // Build array of itinerary legs
            $itineraryLegs = [];
            $itineraryAirlines = [];
            $legArrivalIsNightly = '';
            $scaleDuration = new \DateTime();
            $legPreviousArrivalDateTime = new \DateTime();
            foreach ($itineraryLegsList as $itineraryLegElement) {
                $legDepartureDateTimeString = trim(strVal($itineraryLegElement->DepartureDateTime));
                $legDepartureDateTime = \DateTime::CreateFromFormat($sourceDateTimeFormat, $legDepartureDateTimeString);
                $legArrivalDateTimeString = trim(strVal($itineraryLegElement->ArrivalDateTime));
                $legArrivalDateTime = \DateTime::CreateFromFormat($sourceDateTimeFormat, $legArrivalDateTimeString);
                $legArrivalAirportLocationCode = trim($itineraryLegElement->ArrivalAirportLocationCode);
                $legArrivalAirportTerminal = trim($itineraryLegElement->ArrivalAirportTerminal);
                $legDepartureAirportLocationCode = trim($itineraryLegElement->DepartureAirportLocationCode);
                $legDepartureAirportTerminal = trim($itineraryLegElement->DepartureAirportTerminal);
                $legFlightNumber = trim($itineraryLegElement->FlightNumber);
                $legOperatingCarrierCode = trim($itineraryLegElement->OperatingCarrierCode);
                $legMarketingCarrierCode = trim($itineraryLegElement->MarketingCarrierCode);
                $legAircraftType = trim($itineraryLegElement->AircraftType);

                // Get itinerary airlines
                $itineraryAirlines[] = [
                    'code' => $legOperatingCarrierCode,
                ];

                // Add scale segment
                if (!(empty($itineraryLegs))) {
                    $changeTerminal = (end($itineraryLegs)['arrival']['airport']['terminal'] != $legDepartureAirportTerminal) ? true : false;
                    $scaleDuration = $legPreviousArrivalDateTime->diff($legDepartureDateTime);

                    $itineraryLegs[] = [
                        'type' => 'scale',
                        'changeTerminal' => $changeTerminal,
                        'isNightly' => $legArrivalIsNightly,
                        'duration' => [
                            'hours' => $scaleDuration->h,
                            'minutes' => $scaleDuration->i,
                        ],
                    ];
                }

                $legArrivalIsNightly = self::isNightly($legArrivalDateTime);
                $legPreviousArrivalDateTime = $legArrivalDateTime;

                // segments
                $itineraryLegs[] = [
                    'type' => 'flight',
                    'departure' => [
                        'airport' => [
                            'code' => $legDepartureAirportLocationCode,
                            'terminal' => $legDepartureAirportTerminal,
                        ],
                        'date' => $legDepartureDateTime->format($outputDateFormat),
                        'time' => $legDepartureDateTime->format($outputTimeFormat),
                    ],
                    'arrival' => [
                        'airport' => [
                            'code' => $legArrivalAirportLocationCode,
                            'terminal' => $legArrivalAirportTerminal,
                        ],
                        'date' => $legArrivalDateTime->format($outputDateFormat),
                        'time' => $legArrivalDateTime->format($outputTimeFormat),
                    ],
                    'isNightly' => self::isNightly($legDepartureDateTime),
                    'duration' => [
                        /**
                         * Decieving information
                         *
                         * Can't get an acurate journey duration without time zone.
                         * If there is a way to obtain time zone, use the following code:
                         *
                         *  'hours' => intval($legArrivalDateTime->diff($legDepartureDateTime)->h),
                         *  'minutes' => intval($legArrivalDateTime->diff($legDepartureDateTime)->i),
                         * */
                        'hours' => 'N/A',
                        'minutes' => 'N/A',
                    ],
                    'flightNumber' => $legFlightNumber,
                    'aircraft' => $legAircraftType,
                    'airline' => [
                        'code' => $legMarketingCarrierCode,
                    ],
                    'operatingAirline' => [
                        'code' => $legOperatingCarrierCode,
                    ],
                    'class' => [],
                ];
            }

            $scaleCount = count(array_filter($itineraryLegs, function ($arrayElement) {
                return ($arrayElement['type'] == 'scale');
            }));

            $airItineraries[$itineraryId] = [
                'itineraryAirlines' => $itineraryAirlines,
                'departureDetails' => [
                    'airport' => [
                        'code' => $itineraryDepartureAirportLocationCode,
                    ],
                    'date' => $itineraryDepartureDateTime->format($outputDateFormat),
                    'time' => $itineraryDepartureDateTime->format($outputTimeFormat),
                ],
                'arrivalDetails' => [
                    'airport' => [
                        'code' => $itineraryArrivalAirportLocationCode,
                    ],
                    'date' => $itineraryArrivalDateTime->format($outputDateFormat),
                    'time' => $itineraryArrivalDateTime->format($outputTimeFormat),
                ],
                'journeyDuration' => [
                    'hours' => intval($itineraryTotalDuration->format('H')),
                    'minutes' => intval($itineraryTotalDuration->format('i')),

                ],
                'journeySegments' => $itineraryLegs,
                'journeyScaleCount' => $scaleCount,
            ];
        }

        // Get pricing group list
        $airPricingGroupList = $soapXml->AirAvailSearchResponse->AirAvailSearchResult->AirAvail->AirPricingGroups->AirPricingGroup;

        // Build array of pricing groups
        foreach ($airPricingGroupList as $airPricingGroupElement) {

            // get total price
            $adultTicketAmount = floatval($airPricingGroupElement->AdultTicketAmount);
            $childrenTicketAmount = floatval($airPricingGroupElement->ChildrenTicketAmount);
            $infantTicketAmount = floatval($airPricingGroupElement->InfantTicketAmount);
            $adultTaxAmount = floatval($airPricingGroupElement->AdultTaxAmount);
            $childrenTaxAmount = floatval($airPricingGroupElement->ChildrenTaxAmount);
            $infantTaxAmount = floatval($airPricingGroupElement->InfantTaxAmount);
            $agencyFeeAmount = floatval($airPricingGroupElement->AgencyFeeAmount);
            $aramixFeeAmount = floatval($airPricingGroupElement->AramixFeeAmount);
            $discountAmount = floatval($airPricingGroupElement->DiscountAmount);
            $totalPrice = $adultTicketAmount
                + $childrenTicketAmount
                + $infantTicketAmount
                + $adultTaxAmount
                + $childrenTaxAmount
                + $infantTaxAmount
                + $agencyFeeAmount
                + $aramixFeeAmount
                + $discountAmount;

            // Get list of air pricing group options
            $pricingGroupOptionList = $airPricingGroupElement->AirPricingGroupOptions->AirPricingGroupOption;

            // Build array of pricing group options
            foreach ($pricingGroupOptionList as $pricingGroupOptionElement) {

                // Get list of air priced itineraries
                $pricedItineryList = $pricingGroupOptionElement->AirPricedItineraries->AirPricedItinerary;

                // Build array of air priced itineraries
                $pricedItineraries = [];
                foreach ($pricedItineryList as $pricedItineraryElement) {

                    $journeyId = 1;

                    // Get itinerary details
                    $pricedItineraryId = trim($pricedItineraryElement->ItineraryID);
                    $itineraryDetails = $airItineraries[$pricedItineraryId];

                    // Get list of priced itinerary legs
                    $pricedItineraryLegsList = $pricedItineraryElement->AirPricedItineraryLegs->AirPricedItineraryLeg;
                    $flightSegmentsArray = array_filter($itineraryDetails['journeySegments'], function ($segment) {
                        return array_key_exists('class', $segment);
                    });
                    $flightSegmentsKeys = array_keys($flightSegmentsArray);

                    // Get cabin class and cabin type
                    $i = 0;
                    foreach ($pricedItineraryLegsList as $pricedItineraryLegElement) {
                        $pricedItineraryLegCabinClass = trim($pricedItineraryLegElement->CabinClass);
                        $pricedItineraryLegCabinType = trim($pricedItineraryLegElement->CabinType);
                        $itineraryDetails['journeySegments'][$flightSegmentsKeys[$i]]['class'] = [
                            'code' => $pricedItineraryLegCabinClass,
                            'type' => $pricedItineraryLegCabinType,
                        ];

                        $i++;
                    }

                    $pricedItineraries[] = [
                        'journey' => $journeyId,
                        'airlines' => array_unique($itineraryDetails['itineraryAirlines'], SORT_REGULAR),
                        'departure' => $itineraryDetails['departureDetails'],
                        'arrival' => $itineraryDetails['arrivalDetails'],
                        'duration' => $itineraryDetails['journeyDuration'],
                        'segments' => $itineraryDetails['journeySegments'],
                        'scale' => $itineraryDetails['journeyScaleCount'],
                    ];

                    $journeyId++;
                }

                $pricingGroupOptions[] = [
                    'journeys' => $pricedItineraries,
                    'option' => [
                        'price' => [
                            'amount' => $totalPrice,
                            'currency' => 'USD'
                        ],
                    ],
                ];
            }
        }

        $response = [
            'count' => count($pricingGroupOptions),
            'flights' => $pricingGroupOptions,
        ];

        return $response;
    }
}
