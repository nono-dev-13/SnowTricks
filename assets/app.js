
import './styles/bootstrap.min.css';
import './styles/app.scss';
import $ from 'jquery';


//supprime les images ajax dans page edit
let links = document.querySelectorAll("[data-delete]");

for(let link of links){
    link.addEventListener("click", function (e) {
        e.preventDefault();
        if (confirm('Voulez-vous supprimer cette image ?')) {
            fetch(this.getAttribute('href'), {
                method: "DELETE",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({"_token": this.dataset.token})
            }).then(
                response => response.json()
            ).then(data => {
                if(data.success ) {
                    this.parentElement.parentElement.remove();
                } else {
                    alert(data.error)
                }
            }).catch(e=>alert(e))
        }
    });
}

// add-collection-widget.js
$(document).ready(function () {
    $('.add-another-collection-widget').click(function (e) {
        var list = $($(this).attr('data-list-selector'));
        // Try to find the counter of the list or use the length of the list
        var counter = list.data('widget-counter') || list.children().length;

        // grab the prototype template
        var newWidget = list.attr('data-prototype');
        // replace the "__name__" used in the id and name of the prototype
        // with a number that's unique to your emails
        // end name attribute looks like name="contact[emails][2]"
        newWidget = newWidget.replace(/__name__/g, counter);
        // Increase the counter
        counter++;
        // And store it, the length cannot be used if deleting widgets is allowed
        list.data('widget-counter', counter);

        // create a new list element and add it to the list
        var newElem = $(list.attr('data-widget-tags')).html(newWidget);
        newElem.appendTo(list);
    });
});