var EventManagerGui = Class.create(ATG2, {

    requestCallbackHook : function ($super, data){

        $super(data);

        if(data.action == 'showEventTestResult') {
            $j('#test-result').html(data.data.result);
        }

    },

});
