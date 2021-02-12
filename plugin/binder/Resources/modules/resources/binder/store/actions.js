import cloneDeep from 'lodash/cloneDeep'
import get from 'lodash/get'
import merge from 'lodash/merge'


import {API_REQUEST} from '#/main/app/api'

import {makeActionCreator} from '#/main/app/store/actions'
import {actions as formActions} from '#/main/app/content/form/store/actions'


export const actions = {}

export const BINDER_LOAD = "BINDER_LOAD";
actions.binderLoad = makeActionCreator(BINDER_LOAD);
actions.binderLoadCallback = (binderId) => dispatch => {
	return dispatch({
		[API_REQUEST]: {
			url:['apiv2_sipdt_binder_load', {binderId: binderId}],
			silent: true,
			success: (response) => {
				dispatch(actions.binderLoad(response))
			}
		}
	})
}


