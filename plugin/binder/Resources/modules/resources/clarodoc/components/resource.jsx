import React from 'react'
import {PropTypes as T} from 'prop-types'
import {ResourcePage} from '#/main/core/resource/containers/page'


import {DocumentEditorMain} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/editor/containers/main'
import {DocumentPlayerMain} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/player/containers/main'

/*primaryAction="chapter"
	    customActions={[
	      {
	        type: CALLBACK_BUTTON,
	        icon: 'fa fa-fw fa-clarodoc-pdf-o',
	        displayed: this.props.canExport,
	        label: trans('export-pdf', {}, 'actions'),
	        group: trans('transfer'),
	        callback: () => this.props.downloadLessonPdf(this.props.lesson.id)
	      }
	    ]}*/
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
