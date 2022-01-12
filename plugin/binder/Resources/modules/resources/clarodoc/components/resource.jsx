import React from 'react'
import cloneDeep from 'lodash/cloneDeep'
import omit from 'lodash/omit'
import { PropTypes as T } from 'prop-types'

import { ResourcePage } from '#/main/core/resource/containers/page'
import { Translator, trans } from '#/main/app/intl/translation'
import { Button } from '#/main/app/action/components/button'
import { CALLBACK_BUTTON, LINK_BUTTON } from '#/main/app/buttons'


import { DocumentEditorMain } from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/editor/containers/main'
import { DocumentPlayerMain, DocumentDirectory } from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/player/containers/main'

const DocumentResource = (props) => {

	// const translations = props.clarodoc.translations;
	//  if(translations && Object.keys(translations).length > 0){
	//    for(const field in translations){
	//      for(const locale in translations[field]){
	//        if(translations[field][locale].length > 0){
	//          Translator.add(
	//            field,
	//            translations[field][locale],
	//            `${props.clarodoc.id}`,
	//            locale);
	//        }
	//      }
	//    }
	//  }
	//  const resourceNode = cloneDeep(props.resourceNode);
	//  for(let fieldKey of translations.keys()){
	//  	if(translations[fieldKey].path === "resourceName"){
	//  		let newName = trans('resourceName', {}, `${props.clarodoc.id}`);
	//  		if(newName.length > 0 && newName != "resourceName"){
	//  			resourceNode.name = newName;
	//  		}
	//  	}
	//  }
	//
	const actions = [{
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
		label: trans('display_document', {}, 'actions'),
		target: `${props.path}/${props.resourceNode.slug}`

	}]

	return (
		<ResourcePage
			customActions={actions}
			routes={[
				{
					path: '/edit',
					disabled: !props.editable,
					component: DocumentEditorMain
				}, {
					path: '/resources',
					exact: true,
					component: DocumentDirectory
				}, {
					path: '/',
					component: DocumentPlayerMain
				}
			]} />
	);
}


DocumentResource.propTypes = {
	resourceNode: T.object,
	path: T.string,
	editable: T.bool.isRequired
}

export {
	DocumentResource
}
