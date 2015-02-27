<?php

    /**
     * Migrationsscript von der Version 0.22 zu 0.23
     **/
    class Migration023 extends Migration
    {

        function up ()
        {
        }
    
        function down()
        {
            throw new Exception(_("Ein Downgrade des Plugins ist nicht möglich"));
        }

    }

?>
