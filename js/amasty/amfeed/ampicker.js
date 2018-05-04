document.observe("dom:loaded", function() {
    var lis = document.getElementById("profileTabs").getElementsByTagName("a");
    for (var i = 0; i < lis.length; ++i) {
        var element = document.getElementById(lis[i].id);
        element.addEventListener("click", linkPicker);
    }
})

function linkPicker()
{
    var testLink = document.getElementById('support_link').getAttribute("href");
    var str = this.title;
    var newLink = testLink.replace(/#[a-z]+/,'#'+str.toLowerCase());
    document.getElementById('support_link').href = newLink;
}
