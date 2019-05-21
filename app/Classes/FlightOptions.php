<?php

namespace App\Classes;

use function App\Http\Controllers\isNightly;

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
        // Get list of pricing groups, itineraries from xml
        $pricingGroupList = $soapXml->AirAvailSearchResponse->AirAvailSearchResult->AirAvail->AirPricingGroups->AirPricingGroup;
        $itinerariesList = $soapXml->AirAvailSearchResponse->AirAvailSearchResult->AirAvail->AirItineraries->AirItinerary;

        // Build a relational array of itineraries data
        foreach ($itinerariesList as $itinerary) {
            $Id = trim(strval($itinerary->ItineraryID));
            $departureDateTime = trim(strval($itinerary->DepartureDateTime));
            $ArrivalDateTime = trim(strval($itinerary->ArrivalDateTime));
            $ArrivalAirportLocationCode = trim(strval($itinerary->ArrivalAirportLocationCode));
            $DepartureAirportLocationCode = trim(strval($itinerary->DepartureAirportLocationCode));
            $TotalDuration = trim(strval($itinerary->TotalDuration));

            $itineraries[$Id] = [
                'id' => $Id,
                'DepartureDateTime' => $departureDateTime,
                'ArrivalDateTime' => $ArrivalDateTime,
                'ArrivalAirportLocationCode' => $ArrivalAirportLocationCode,
                'DepartureAirportLocationCode' => $DepartureAirportLocationCode,
                'TotalDuration' => $TotalDuration,
            ];
        }


        // Get pricing groups
        foreach ($pricingGroupList as $pricingGroup) {

            // Get pricing options
            $pricingOptionList = $pricingGroup->AirPricingGroupOptions->AirPricingGroupOption;
            $journeys = [];
            foreach ($pricingOptionList as $pricingOption) {
                $itineraryId = trim(strval($pricingOption->AirPricedItineraries->AirPricedItinerary->ItineraryID));

                // Get cabin class and cabin type
                $itineraryLeg = trim(strval($pricingOption->AirPricedItineraries->AirPricedItinerary->AirPricedItineraryLegs->AirPricedItineraryLeg));
                $cabinClass = trim(strval($itineraryLeg->CabinClass));
                $cabinType = trim(strval($itineraryLeg->CabinType));

                // Build journeys
                $journeys[] = [
                    'jo u  rney' => '/*Id del trayecto */',

                    $itineraries[$itineraryId]
                ];
            }


            // Get price data
            $adultTicketAmount = floatval($pricingGroup->AdultTicketAmount);
            $childrenTicketAmount = floatval($pricingGroup->ChildrenTicketAmount);
            $infantTicketAmount = floatval($pricingGroup->InfantTicketAmount);
            $adultTaxAmount = floatval($pricingGroup->AdultTaxAmount);
            $childrenTaxAmount = floatval($pricingGroup->ChildrenTaxAmount);
            $infantTaxAmount = floatval($pricingGroup->InfantTaxAmount);
            $agencyFeeAmount = floatval($pricingGroup->AgencyFeeAmount);
            $aramixFeeAmount = floatval($pricingGroup->AramixFeeAmount);
            $discountAmount = floatval($pricingGroup->DiscountAmount);
            $totalAmount = $adultTicketAmount
                + $childrenTicketAmount
                + $infantTicketAmount
                + $adultTaxAmount
                + $childrenTaxAmount
                + $infantTaxAmount
                + $agencyFeeAmount
                + $aramixFeeAmount
                + $discountAmount;
            $currency = 'USD';




            // Build flights
            $flights[] = [
                'journeys' => $journeys,
                'option' => [
                    'price' => [
                        'amount' => $totalAmount,
                        'currency' => $currency,
                    ],
                ],
            ];
        }


        $response = [
            "count" => count($flights),
            "flights" => $flights,
            "raw data" => [
                'pricingGroups' => $pricingGroupList,
                'itineraries' => $itinerariesList,
            ]
        ];


        return $response;
    }
}
