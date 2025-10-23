(function (){

    if (typeof BX === 'undefined' || typeof BX.ajax === 'undefined' || window.ymecInited) {
        return;
    }

    window.ymecInited = true;

    let hasActiveRequest = false;

	let ymUid = localStorage.getItem('_ym_uid')
	if(ymUid) {
		ymUid = ymUid.replaceAll('"', '');
	}

    BX.ready(function(){
        requestEcommerceActions(true);

        if (typeof jQuery === 'function') {
            jQuery(document).on('ajaxSuccess', function(r){
                if (hasActions()) {
                    requestEcommerceActions();
                }
            });
        }



        BX.addCustomEvent('onAjaxSuccess', function(oResponse, r2, r3){
            //if (typeof oResponse !== 'undefined' && oResponse && !oResponse.ecActions && oResponse.status !== 'error') {
                if (hasActions()) {
                    requestEcommerceActions();
                }
            //}
        });

        document.addEventListener("DOMContentLoaded", function() {
            if (typeof jQuery === 'function') {
                jQuery(document).on('shown.bs.modal', function (e) {
                    let form = e.target.querySelector('form');

                    if ((form !== undefined) && (form !== null)) {
                        let counters = window.counters;
                        for (var key in counters) {
                            ym(counters[key], 'reachGoal', 'ym-open-leadform');
                        }
                    }
                });
            }

            if (typeof $.magnificPopup === "object") {
                $.magnificPopup.instance.open = function (data) {
                    $.magnificPopup.proto.open.call(this, data);

                    let form = $.magnificPopup.instance.wrap[0].querySelector('form');
                    if ((form !== undefined) && (form !== null)) {
                        let counters = window.counters;
                        for (var key in counters) {
                            ym(counters[key], 'reachGoal', 'ym-open-leadform');
                        }
                    }
                };
            }

            if (typeof $.fancybox === 'object') {
                $.fancybox.defaults.afterShow = function (fancy) {
                    let form = fancy.$refs.container[0].querySelector('form');

                    if ((form !== undefined) && (form !== null)) {
                        let counters = window.counters;
                        for (var key in counters) {
                            ym(counters[key], 'reachGoal', 'ym-open-leadform');
                        }
                    }
                };
            }

            if (typeof Fancybox === 'function') {
                Fancybox.bind('[data-fancybox]', {
                        on: {
                            done: (fancybox, slide) => {
                                let form = slide.$content.querySelector('form');

                                if ((form !== undefined) && (form !== null)) {
                                    let counters = window.counters;
                                    for (var key in counters) {
                                        ym(counters[key], 'reachGoal', 'ym-open-leadform');
                                    }
                                }
                            }
                        }
                    }
                );
            };

        });
    });

    function requestEcommerceActions(processInstant){
        if (hasActiveRequest) {
            return;
        }

        hasActiveRequest = true;
		let data = {}
	    if(window.basketSettings) {
		    data[window.basketSettings.key] = window.basketSettings.val;
	    }
	    data['_ym_uid'] = ymUid;
	    BX.ajax.runAction('yandex:metrika.yandex_metrika.Ajax.getEcommerceActions', {
		    data: data
	    })
		    .then(function(oResponse) {
                if (oResponse.status === 'success') {
                    if (typeof oResponse.data.actions !== 'undefined') {
                        const actions = oResponse.data.actions;
                        const actionsIds = Object.keys(actions);
                        if (processInstant) {
                            processActions(actions);
                            removeSentActions(actionsIds);
                            hasActiveRequest = false;
                        } else {
                            setTimeout(function () {
                                processActions(actions);
                                removeSentActions(actionsIds);
                                hasActiveRequest = false;
                            }, 2000);
                        }
                    }
                }

                oResponse.ecActions = true;
                return oResponse;
            }, function (oResponse) {
                oResponse.ecActions = true;
                hasActiveRequest = false;
                return oResponse;
            });
    }


    function processActions(actions){
        let formSended = false;
	    if (actions.length !== 0) {
		    localStorage.setItem('purchaseItems', JSON.stringify(actions))
	    }
        for (let id in actions) {

            let action = actions[id];

            if(Object.hasOwn(action, 'ecommerce'))
				if(window.dataLayerName && window[window.dataLayerName]) {
					if(action)
					window[window.dataLayerName].push(action);
				}
            else {
                if(formSended == false) {
                    let counters = window.counters;
                    for (var key in counters) {
                        ym(counters[key], 'reachGoal', actions[id][0]);
                    }
                    formSended = true;
                }
            }
        }
    }

	function processPurchase() {
		requestEcommerceActions(true)

		/*let formSended = false;
		let actions = JSON.parse(localStorage.getItem('purchaseItems'))

		console.log('processPurchase', actions)


		const actionsArr = Array.isArray(actions) ? actions : Object.values(actions);
		const purchaseAction = JSON.parse(JSON.stringify(actionsArr[actionsArr.length - 1]));

		if (purchaseAction.ecommerce && purchaseAction.ecommerce.add) {
			purchaseAction.ecommerce.purchase = purchaseAction.ecommerce.add;
			delete purchaseAction.ecommerce.add;
		}

		const keys = Object.keys(actions);
		const lastKey = keys.length ? Math.max(...keys.map(Number)) : 0;
		const newKey = String(lastKey + 1);

		let productList = {};
		for (let id in actions) {
			let item = actions[id].ecommerce.add.products[0]
				key = item.id
			productList[key] = item
		}

		purchaseAction.ecommerce.purchase.products = productList

		actions[newKey] = purchaseAction;


		console.log(actions)


		for (let id in actions) {

			let action = actions[id];

			if (Object.hasOwn(action, 'ecommerce'))
				console.log(action)
			console.log(action.ecommerce.purchase)
			console.log('window.orderNum', window.orderNum)
			if (action.ecommerce.purchase) {
				action.ecommerce.purchase.actionField = {id: window.orderNum}
			}
				console.log('add Data Layer', action)
			if (window.dataLayerName && window[window.dataLayerName]) {
				window[window.dataLayerName].push(action);
			} else {
				if (formSended == false) {
					let counters = window.counters;
					for (var key in counters) {
						ym(counters[key], 'reachGoal', actions[id][0]);
					}
					formSended = true;
				}
			}
		}





		requestEcommerceActions(true)

		console.log('result action', actions)*/


	}

    function removeSentActions(actionsIds){
	    let data = {}
	    if(window.basketSettings) {
		    data[window.basketSettings.key] = window.basketSettings.val;
	    }
		data['actionsIds'] = actionsIds;
	    data['_ym_uid'] = ymUid;
        BX.ajax.runAction('yandex:metrika.yandex_metrika.Ajax.removeEcommerceActions', {
            data: data
        })
            .then(function(oResponse) {
                oResponse.ecActions = true;
                return oResponse;
            }, function (oResponse) {
                oResponse.ecActions = true;
                return oResponse;
            });

        BX.setCookie('ym_has_actions', '', {
            expires: -1000,
            path: '/'
        });
    }

    function addAction(type){
	    let data = {}
	    if(window.basketSettings) {
		    data[window.basketSettings.key] = window.basketSettings.val;
	    }
		data['type'] = type;
	    data['_ym_uid'] = ymUid;
        BX.ajax.runAction('yandex:metrika.yandex_metrika.Ajax.addEcommerceActions', {
            data: data
        })
            .then(function(oResponse) {
                oResponse.ecActions = true;
                return oResponse;
            }, function (oResponse) {
                oResponse.ecActions = true;
                return oResponse;
            });
    }

    function hasActions(){
        return BX.getCookie('ym_has_actions');
    }


	window.requestEcommerceActions = requestEcommerceActions;
	window.addEcommerceAction = addAction;
	window.processPurchase = processPurchase;
})();