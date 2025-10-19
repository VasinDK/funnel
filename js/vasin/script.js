BX.ready(function() {
    const recipientButton = document.getElementById('recipient-button');
    let dialog = new BX.UI.EntitySelector.Dialog({
        targetNode: recipientButton,
        context: 'USER',
        enableSearch: true,
        searchOptions: {
            allowCreateItem: false,
        },
        multiple: true,
        entities: [
            {
                id: 'user',
                dynamicLoad: true,
                dynamicSearch: true,
            },
        ],
    });

    recipientButton.addEventListener('click', () => {
        BX.toggleClass(BX('recipient-button'), 'ui-btn-wait')

        BX.ajax.runAction('dk:vasin.recipient.get', {
            method: 'GET',
        }).then(function (response) {
            dialog.setPreselectedItems(response.data);
            dialog.show();
            BX.toggleClass(BX('recipient-button'), 'ui-btn-wait');
        }).catch(function (response) {
            BX.UI.Notification.Center.notify({
                content:  BX.message('VASIN_ERROR_RECEIVING_DATA') + response.errors[0].message,
                autoHideDelay: 5000
            });
            dialog.hide()
        })
    });

    new BX.UI.LayoutForm();

    const save = document.getElementById('save');
    save.addEventListener('click', () => {
        BX.toggleClass(BX('save'), 'ui-btn-wait')

        const ids = dialog.getSelectedItems().map((item, _) => {
            return {
                userId: item.id,
            }
        })

        const promise1 = BX.ajax.runAction('dk:vasin.recipient.upsert', {
            data: {
                items: ids
            },
        });

        let index;
        let resultCheckboxes = [];
        let checkboxes = document.querySelectorAll('.checkbox-vasin');
        let input = document.querySelectorAll('.input-vasin');

        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                index = checkbox.getAttribute('index');

                resultCheckboxes.push({
                    'dealCategoryId': Number(checkbox.id),
                    'daysDelay': Number(input[index].value),
                });
            }
        });

        const promise2 = BX.ajax.runAction('dk:vasin.funnelMonitor.upsert', {
            data: {
                items: resultCheckboxes,
            },
        });

        Promise.all([promise1, promise2])
            .then(function(response) {
                BX.UI.Notification.Center.notify({
                    content: BX.message('VASIN_SETTINGS_SAVED'),
                    autoHideDelay: 3000
                });
                BX.toggleClass(BX('save'), 'ui-btn-wait')
            })
            .catch(function(response){
                BX.UI.Notification.Center.notify({
                    content:  BX.message('VASIN_SAVING_ERROR') + response.errors[0].message,
                    autoHideDelay: 5000
                });
            });
    });

    const start = document.getElementById('start');
    start.addEventListener('click', () => {
        BX.toggleClass(BX('start'), 'ui-btn-wait')

            BX.ajax.runAction('dk:vasin.funnelMonitor.check', {
        })
            .then(function(response) {
                BX.UI.Notification.Center.notify({
                    content:  BX.message('VASIN_NOTIFICATION_PROC_START'),
                    autoHideDelay: 3000
                });
                BX.toggleClass(BX('start'), 'ui-btn-wait')
            })
            .catch(function(response){
                BX.UI.Notification.Center.notify({
                    content: BX.message('VASIN_ERROR') + response.errors[0].message,
                    autoHideDelay: 5000
                });
            });
    })
});
