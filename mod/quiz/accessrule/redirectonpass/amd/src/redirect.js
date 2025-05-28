define([], function() {
    return {
        init: function(cmid) {
            // Change 'assign' to another mod type if needed
			console.log('redirectonpass: Change event listener attached to checkbox.' . cmid); // Debug log 1
            var url = M.cfg.wwwroot + '/mod/quiz/view.php?id=' + cmid;
            var div = document.createElement('div');
            div.innerHTML = 'Congratulations! Redirecting you to the next activity...';
            div.style = 'background:#ffd; color:#222; padding:10px; text-align:center; font-weight:bold;';
            document.body.insertBefore(div, document.body.firstChild);
            setTimeout(function() { window.location = url; }, 2000);
        }
    };
});