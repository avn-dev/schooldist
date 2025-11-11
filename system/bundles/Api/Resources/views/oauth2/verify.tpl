<script>

    var params = {$json|json_encode};

    if (window.opener) {
        // send them to the opening window
        window.opener.postMessage(params);
        // close the popup
        window.close();
    } else {
        console.error('no window opener')
    }
</script>