// var webPage = require('webpage');
// var page = webPage.create();

// page.open('http://grafitti.loveslife.biz/relay.php?url=https://cdn4.geckoandfly.com/wp-content/uploads/2015/05/plato-quotes-01.jpg', function(status) {
// 	console.log(page.content);
// 	phantom.exit();
// });



var givenUrl = "http://grafitti.loveslife.biz/relay.php?url=https://cdn4.geckoandfly.com/wp-content/uploads/2015/05/plato-quotes-01.jpg";
var urls = [];
getUrls(givenUrl, urls);
setTimeout(print, 3000);

// get all potential redirected urls
function getUrls(givenUrl, urls) {
	var page = require('webpage').create();

	page.onUrlChanged = function(newUrl) {
		urls.push(newUrl);
	}

	page.open(givenUrl);
}

function print() {
	phantom.exit();
}