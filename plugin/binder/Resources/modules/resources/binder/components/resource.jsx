import React from 'react'
import {PropTypes as T} from 'prop-types'
import {ResourcePage} from '#/main/core/resource/containers/page'

import {trans} from '#/main/app/intl/translation'
import {CALLBACK_BUTTON} from '#/main/app/buttons'

import {BinderEditorMain} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/editor/containers/main'
import {BinderPlayerMain} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/player/containers/main'


const BinderResource = (props) => {
	return (
		<ResourcePage
			routes={[
				{
					path: '/edit',
					disabled: !props.editable,
					component: BinderEditorMain
				},{
					path: '/',
					exact: true,
					component: BinderPlayerMain
				},{
		            path: '/:path',
		            component: BinderPlayerMain,
		            exact: true,
		            onEnter: params => this.props.loadSection(this.props.binder, params.path)
	          	}
			]}
		/>
	);
}
	


BinderResource.propTypes = {
  binder:T.object,
  editable: T.bool.isRequired,
  loadSection:T.func,
  update: T.func
}

export {
  BinderResource
}
