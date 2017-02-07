var eventUList,
	updateEventInfoButton = jQuery('.js-update-event-info'),
	eventList,
	pickedEvent;



jQuery('document').ready(function() {
	eventUList = jQuery('.js-event-list');

	eventUList.on('change', 'input[name=svid_facebook_connect_event_id]', function() {
	   eventList.forEach(function(e) {
		   var newId = jQuery('input[name=svid_facebook_connect_event_id]:checked').val();
		   if (e.id === newId) pickedEvent = e;
	   });
	});
});



jQuery.ajax({
	url: ajaxurl,
	data: { action: "event_list" },
	success: function(data) {
		eventList = data["events"];
		eventList.forEach(createAndAppendEvent);
	}
});

function createAndAppendEvent(e) {
	var eventRadio = jQuery('<input type="radio">'),
		eventListItem = jQuery('<li>' + e.name + '</li>');

	eventRadio.attr('name', 'svid_facebook_connect_event_id');
	eventRadio.attr('value', e.id);
	if (eventUList.attr('data-event-picked') === e.id) {
		eventRadio.attr('checked', true);
		pickedEvent = e;
	}

	eventListItem.prepend(eventRadio);

	eventUList.append(eventListItem);
}



updateEventInfoButton.click(function() {
	console.log('Updating event info');
	jQuery('#title').val(pickedEvent.name);
	jQuery('#acf-field-start_datetime').val(pickedEvent.start_time.date);
	jQuery('#acf-field-end_datetime').val(pickedEvent.end_time.date);
});
