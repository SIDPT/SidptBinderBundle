import React from 'react'
import {PropTypes as T} from 'prop-types'
import {ResourcePage} from '#/main/core/resource/containers/page'


import {DocumentEditorMain} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/editor/containers/main'
import {DocumentPlayerMain} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/player/containers/main'


const DocumentResource = (props) =>  
	<ResourcePage
	    routes={[
		  {
	        path: '/edit',
	        disabled: !props.canEdit,
	        component: DocumentEditorMain
	      },{
	        path: '/',
	        exact: true,
	        component: DocumentPlayerMain
	      }
	    ]}
  	/>;


DocumentResource.propTypes = {
  canEdit: T.bool.isRequired
}

export {
  DocumentResource
}
