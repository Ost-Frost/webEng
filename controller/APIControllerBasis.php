<?php
    require("ControllerBasis.php");
    /**
     * base controller. All controllers have to extend this class
     */
    abstract class APIControllerBasis extends ControllerBasis {

        /**
         * abstract method that redirects an action to the corresponding method in the child class
         *
         * @param string action string
         * @param ModelBasis corresponding data model for database actions
         *
         * @return string response strin of the API call
         */
        abstract public function apiCall($action, $model) : string;

        /**
         * rejects the API call by returning empty JSON string and sending an error code
         *
         * @param int error code
         *
         * @return string empty JSON string
         */
        public function rejectAPICall($error) : string {
            http_response_code($error);
            return "{}";
        }

        /**
         * resolves the API call by returning the response string and sending the status code
         *
         * @param string response string, default empty JSON string
         * @param int status code, default OK 200
         *
         * @return string response string
         */
        public function resolveAPICall($response="{}", $status=200) : string {
            http_response_code($status);
            return $response;
        }
    }

?>