import {makeInstanceAction} from '#/main/app/store/actions'
import {combineReducers, makeReducer} from '#/main/app/store/reducer'
import {makeFormReducer} from '#/main/app/content/form/store/reducer'

import {RESOURCE_LOAD} from '#/main/core/resource/store/actions'

import {selectors} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/store/selectors'


/**
 * Notes : 
 * The clarodoc subobject is generated by the DocumentListener object
 * the content of this subobject is created by the serializer
 * 
 */
const reducer = combineReducers({
  clarodoc: makeFormReducer(selectors.FORM_NAME, {}, {
    data: makeReducer({}, {
      [makeInstanceAction(RESOURCE_LOAD, selectors.STORE_NAME)]: (state, action) => action.resourceData.clarodoc
    }),
    originalData: makeReducer({}, {
      [makeInstanceAction(RESOURCE_LOAD, selectors.STORE_NAME)]: (state, action) => action.resourceData.clarodoc
    })
  })
})

export {
  reducer
}
