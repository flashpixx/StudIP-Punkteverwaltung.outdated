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


    
    echo <<<EOT
        <script type="text/javascript">

        jQuery(document).ready(function() {

            jQuery("#punktetabelle").jtable({

                title          : "Punktetabelle",
                paging         : true,
                pageSize       : 30,
                sorting        : true,
                defaultSorting : "Name ASC",
                actions: {
                    listAction   : "$listaction",
                    updateAction : "$updateaction",
                },

                fields: {

                    Auth : {
                        key    : true,
                        create : false,
                        edit   : false,
                        list   : false
                    },

                    Matrikelnummer : {
                        edit   : false,
                        title  : "Matrikelnummer",
                        width  : "10%"
                    },

                    Name : {
                        edit  : false,
                        title : "Name",
                        width : "25%"
                    },

                    EmailAddress : {
                        edit  : false,
                        title : "EMail Adresse",
                        width : "15%"
                    },
                                                           
                    ErreichtePunkte : {
                        title : "erreichte Punkte",
                        width : "10%"
                    },
                                                           
                    ZusatzPunkte : {
                        title : "Zusatzpunkte",
                        width : "10%"
                    },
                                                           
                    Bemerkung : {
                        title : "Bemerkung",
                        width : "30%"
                    }
                                                           
                }
            });
                           
            jQuery("#punktetabelle").jtable("load");
                           
    });
    </script>
EOT;

    echo "<div id=\"punktetabelle\"></div>";


?>