var CleaningTypesGui = Class.create(ATG2, {

    requestCallbackHook : function ($super, data){

        $super(data);

        if(
            (
                data.action == 'openDialog' ||
                data.action == 'saveDialogCallback'
            ) &&
            data.data.additional == null &&
            (
                data.data.action == 'new' ||
                data.data.action == 'edit'
            )
        ) {

            $j('.joined_object_container_cycles .GUIDialogJoinedObjectContainerRow').each(function(index, container) {
                this.prepareJoinedObjectContainer(container);
            }.bind(this));

        }

    },

    changeCycleMode: function(dialogData, event) {
        var container = $(event.target).closest('.GUIDialogJoinedObjectContainerRow');
        this.prepareJoinedObjectContainer(container);
    },

    prepareJoinedObjectContainer: function(container) {

        var mode = $j(container).find('.cycle_mode');
        //var dependencyRow = $j(container).find('.dependency_row');
        var countModeSelect = $j(container).find('.cycle_count_mode');
        var timeSelect = $j(container).find('.cycle_time');
        var weekdaySelect = $j(container).find('.cycle_weekday');

        switch($j(mode).val()) {
            case "fix_bed":
                //$j(dependencyRow).show();
                $j(countModeSelect).val('weeks');
                $j(countModeSelect).attr('disabled','disabled');
                $j(timeSelect).hide();
                $j(weekdaySelect).show();
                break;
            case "regular_bed":
                $j(timeSelect).show();
                $j(timeSelect).val('after_arrival');
                $j(timeSelect).attr('disabled','disabled');
                $j(weekdaySelect).hide();
                break;
            default:
                //$j(dependencyRow).hide();
                $j(countModeSelect).removeAttr('disabled');
                $j(timeSelect).removeAttr('disabled');
                $j(timeSelect).show();
                $j(weekdaySelect).hide();

        }

    }

});
