import React from 'react'
import {PropTypes as T} from 'prop-types'
import {ResourcePage} from '#/main/core/resource/containers/page'

import {trans} from '#/main/app/intl/translation'
import {CALLBACK_BUTTON} from '#/main/app/buttons'

import {BinderEditorMain} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/editor/containers/main'
import {BinderPlayerMain} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/player/containers/main'


const BinderResource = (props) =>  
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
			}
		]}
	/>;


BinderResource.propTypes = {
  binder:T.object,
  editable: T.bool.isRequired,
  update: T.func
}

export {
  BinderResource
}

/**
 * customActions={[
			{
				type: CALLBACK_BUTTON,
				icon: 'fa fa-fw fa-plus',
				label: trans('add_tab'),
				displayed: props.editable,
				callback: () => {
					var tabs = props.binder.tabs;
					tabs.push({
						id:null,
						metadata:{
							type:'undefined',
							roles:[]
						},
						content:undefined

					})
					//props.update('tabs',tabs)
				},
				group: trans('general')
			}
		]}
 */