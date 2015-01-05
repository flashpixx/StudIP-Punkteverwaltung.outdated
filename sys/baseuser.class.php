<?php
    
    /**
    @cond
    ############################################################################
    # GPL License                                                              #
    #                                                                          #
    # This file is part of the StudIP-Punkteverwaltung.                        #
    # Copyright (c) 2013, Philipp Kraus, <philipp.kraus@tu-clausthal.de>       #
    # This program is free software: you can redistribute it and/or modify     #
    # it under the terms of the GNU General Public License as                  #
    # published by the Free Software Foundation, either version 3 of the       #
    # License, or (at your option) any later version.                          #
    #                                                                          #
    # This program is distributed in the hope that it will be useful,          #
    # but WITHOUT ANY WARRANTY; without even the implied warranty of           #
    # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            #
    # GNU General Public License for more details.                             #
    #                                                                          #
    # You should have received a copy of the GNU General Public License        #
    # along with this program. If not, see <http://www.gnu.org/licenses/>.     #
    ############################################################################
    @endcond
    **/

    
    
    require_once("studipincludes.php");
    
    
    /** Exception, um einen User, der nicht gefunden wurde, zu erkennen **/
    class UserNotFound extends Exception {}
    
    /** Exception, die geworfen wird, wenn die Userdaten nicht vollst‰ndig sind **/
    class UserDataIncomplete extends Exception {}
    
    
    
    /** Klasse, um einen Benutzer darzustellen **/
    class BaseUser
    {
    
        /** speichert die UserID des Studenten **/
        protected $mcID     = null;
    
        /** speichert den Namen des Users **/
        protected $mcName   = null;
    
        /** speichert die EMail des Users **/
        protected $mcEmail  = null;
    
    
    
        /** Ctor um einen User zu erzeugen
         * @param $px Userobjekt oder AuthentifizierungsID
         **/
        function __construct( $px )
        {
            $loUser = null;
    
            if ($px instanceof $this)
            {
                $this->mcID             = $px->mcID;
                $this->mcName           = $px->mcName;
                $this->mcEmail          = $px->mcEmail;
            
                $loUser                 = new User($this->mcID);
            
            } elseif (is_string($px)) {
                
                $loUser        = new User($px);
                // der Name wird in der Form "Nachname, Vorname" ausgegeben
                $this->mcName  = $loUser->getFullName("full_rev");
                $this->mcEmail = User::find($px)->email;
                $this->mcID    = $px;
            
            } else
                throw new UserNotFound(_("Userdaten-Eingabe inkorrekt"));

        
            if ( (empty($this->mcID)) || (!is_object($loUser)) || (empty($loUser)) )
                throw new UserNotFound(_("User konnte nicht ermittelt werden"));
            if (!UserModel::check($this->mcID))
                throw new UserDataIncomplete( sprintf( _("Userdaten zum Login: [%s] / EMail: [%s] konnten nicht ermittelt werden."), $loUser->username, $loUser->email ) );
        }
    
    
        /** liefert die ID des Users
         * @return ID
         **/
        function id()
        {
            return $this->mcID;
        }
    
    
        /** liefert den vollständigen Namen des Users
         * @return Name
         **/
        function name()
        {
            return $this->mcName;
        }
    
    
        /** liefert die EMail Adresse des Users
         * @return EMail
         **/
        function email()
        {
        return $this->mcEmail;
        }
    
    
    }



?>
