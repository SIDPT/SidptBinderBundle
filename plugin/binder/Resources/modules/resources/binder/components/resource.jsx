import React from 'react'
import {PropTypes as T} from 'prop-types'
import {ResourcePage} from '#/main/core/resource/containers/page'

import {trans} from '#/main/app/intl/translation'
import {CALLBACK_BUTTON, LINK_BUTTON} from '#/main/app/buttons'

import {BinderEditorMain} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/editor/containers/main'
import {BinderPlayerMain, BinderDirectory} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/player/containers/main'


const BinderResource = (props) => {
	return (
		<ResourcePage
			customActions={
				[{
				  name: 'display_folder',
				  type: LINK_BUTTON,
				  icon: 'fa fa-fw fa-folder',
				  label: trans('display_folder', {}, 'actions'),
				  primary: true,
				  target: `${props.path}/${props.resourceNode.slug}/resources`
				},
				{
				  name: 'display_binder',
				  type: LINK_BUTTON,
				  icon: 'fa fa-fw fa-folder',
				  label: trans('display_binder', {}, 'actions'),
				  primary: true,
				  target: `${props.path}/${props.resourceNode.slug}`

				}]
			}
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
	          		path:'/resources',
	          		exact: true,
	          		component:BinderDirectory
	          	},{
		            path: '/section/:path',
		            component: BinderPlayerMain,
		            exact: true,
		            onEnter: params => props.loadSection(props.binder, params.path)
	          	}
			]}
		/>
	);
}
	


BinderResource.propTypes = {
  binder:T.object,
  resourceNode:T.object,
  path:T.string,
  editable: T.bool.isRequired,
  loadSection:T.func,
  update: T.func
}

export {
  BinderResource
}
