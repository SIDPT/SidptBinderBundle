import cloneDeep from 'lodash/cloneDeep'
import get from 'lodash/get'
import merge from 'lodash/merge'


import {API_REQUEST} from '#/main/app/api'

import {makeActionCreator} from '#/main/app/store/actions'
import {actions as formActions} from '#/main/app/content/form/store/actions'


export const actions = {}

export const BINDER_LOAD = "BINDER_LOAD";
actions.binderLoad = makeActionCreator(BINDER_LOAD, 'response');
actions.getBinder = (binderId) => dispatch => {
	return dispatch({
		[API_REQUEST]: {
			url:['apiv2_sidpt_get_binder', {id: binderId}],
			silent: true,
			success: (response) => {
				dispatch(actions.binderLoad(response))
			}
		}
	})
}

// TO be moved in document actions
export const DOCUMENT_LOAD = "DOCUMENT_LOAD";
actions.documentLoad = makeActionCreator(DOCUMENT_LOAD , 'response');
actions.getDocument = (documentId) => dispatch => {
	return dispatch({
		[API_REQUEST]: {
			url:['apiv2_sidpt_get_document', {id: documentId}],
			silent: true,
			success: (response, dispatch) => {
				dispatch(actions.documentLoad(response))
			}
		}
	})
}

// export const BINDER_TAB_LOAD = "BINDER_TAB_LOAD";
// actions.contentLoad = makeActionCreator(BINDER_TAB_LOAD, 'response');

actions.getBinderTabContent = (tab) => dispatch => {
	if(tab.metadata.type === 'binder' && tab.content != null){
		const binder = tab.content;
		// Tabs are already loaded, dispatch loading of the binder
		// maybe also add the tab to a tabstack 
		// in this place instead of the navigator
		dispatch(actions.binderLoad({
			"binder":binder
		}))
		// get first tab content
		if (binder.tabs && binder.tabs.length > 0) {
			return dispatch(actions.getBinderTabContent(binder.tabs[0]))
		}
	} else return dispatch({
		[API_REQUEST]: {
			url:['sidpt_get_binder_tab_content', {id:tab.id}],
			silent: true,
			success: (response, dispatch) => {
				if (response.binder){
					dispatch(actions.binderLoad(response))
				}
				if(response.clarodoc){
					response.slug = tab.slug;
					dispatch(actions.documentLoad(response))
				}
				
			}
		}
	})
}
actions.reset = (binder) => dispatch => {
	dispatch(actions.binderLoad({
		"binder":binder
	}))
	// get first tab content
	if (binder.tabs && binder.tabs.length > 0) {
		return dispatch(actions.getBinderTabContent(binder.tabs[0]))
	}
}


