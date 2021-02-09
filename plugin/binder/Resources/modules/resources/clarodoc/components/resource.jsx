import React from 'react'
import cloneDeep from 'lodash/cloneDeep'
import {PropTypes as T} from 'prop-types'
import {ResourcePage} from '#/main/core/resource/components/page'
import {Translator, trans} from '#/main/app/intl/translation'
import omit from 'lodash/omit'

import {DocumentEditorMain} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/editor/containers/main'
import {DocumentPlayerMain} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/player/containers/main'


const DocumentResource = (props) => {
	
	const translations = props.clarodoc.translations;
    if(Object.keys(translations).length > 0){
      console.log(translations);
      for(const field in translations){
        for(const locale in translations[field]){
          if(translations[field][locale].length > 0){
            Translator.add(
              field,
              translations[field][locale],
              `${props.clarodoc.id}`,
              locale);
          }
        }
      }
    }
    const resourceNode = cloneDeep(props.resourceNode);
    for(let fieldKey of translations.keys()){
    	if(translations[fieldKey].path === "resourceName"){
    		let newName = trans('resourceName', {}, `${props.clarodoc.id}`);
    		if(newName.length > 0 && newName != "resourceName"){
    			resourceNode.name = newName;			
    		}
    	}
    }

    console.log(resourceNode);
	return (
		<ResourcePage
			basePath={props.basePath}
			contextType={props.contextType}
			embedded={props.embedded}
			showHeader={props.showHeader}
			managed={props.managed}
			userEvaluation={props.userEvaluation}
			accessErrors={props.accessErrors}
			resourceNode={resourceNode}
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
  		/>
  	);
} 


DocumentResource.propTypes = {
  resourceNode:T.object,
  clarodoc:T.object,
  canEdit: T.bool.isRequired
}

export {
  DocumentResource
}
