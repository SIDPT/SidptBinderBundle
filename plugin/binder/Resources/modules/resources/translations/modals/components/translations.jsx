import React, {Component, Fragment} from 'react'
import {PropTypes as T} from 'prop-types'
import omit from 'lodash/omit'
import get from 'lodash/get'
import set from 'lodash/set'
import cloneDeep from 'lodash/cloneDeep'
import {trans, Translator} from '#/main/app/intl/translation'
import {Modal} from '#/main/app/overlays/modal/components/modal'
import {FormData} from '#/main/app/content/form/components/data'

import {Select} from '#/main/app/input/components/select'

// We assume a translation object is structured as 
// [ {path:"fieldpath", locales:{en:'content', fr:'contenu'} }, ... ]

const localesList = [
    'en','de','fr','es','it','nl'
  ];

if(!localesList.includes(Translator.locale)){
  localesList.push(Translator.locale);
}

localesList.sort();

class TranslationsModal extends Component {
  
  constructor(props) {
    super(props);
    
    const selectedFieldIndex = 0;

    const availableLocales = [];
    const currentTranslations = Object.keys(
      this.props.translations[selectedFieldIndex].locales
    );
    
    for(const locale of localesList){
      if(!currentTranslations.includes(locale)){
        availableLocales.push(locale);
      }
    }

    this.state = {
      data:{
        translations: this.props.translations,
      },
      selectedFieldIndex:selectedFieldIndex,
      availableLocales:availableLocales,
      selectedLocale:availableLocales[0]
    }

    
    this.updateProp = this.updateProp.bind(this);
    this.save = this.save.bind(this);

    this.addLocal = this.addLocal.bind(this);
    this.updateSelectedLocale = this.updateSelectedLocale.bind(this);
    this.updateSelectedField = this.updateSelectedField.bind(this);
  }

  updateSelectedField(index){
    // Update available locales
    const availableLocales = [];
    const currentTranslations = Object.keys(
      this.props.translations[index].locales
    );
    
    for(const locale of localesList){
      if(!currentTranslations.includes(locale)){
        availableLocales.push(locale);
      }
    }
    this.setState({
      selectedFieldIndex:index,
      availableLocales:availableLocales,
      selectedLocale:availableLocales[0]
    });
  }

  updateSelectedLocale(value){
    if(this.state.availableLocales.includes(value)){
      this.setState({
        selectedLocale:value
      }); 
    }
  }

  addLocal(){
    const localeIndex = this.state.selectedLocale !== '' ? 
      this.state.availableLocales.indexOf(this.state.selectedLocale):
      -1;
    if(localeIndex >= 0){
      const availableLocales = this.state.availableLocales.splice(localeIndex,1);
      this.updateProp(
        `translations[${this.state.selectedFieldIndex}].locales[${this.state.selectedLocale}]`,'');
      this.setState({
        availableLocales: availableLocales,
        selectedLocale: availableLocales[0]
      });
    }
  }
  

  updateProp(propPath, propValue) {
    const newData = cloneDeep(this.state.data);
    set(newData,propPath,propValue);
    console.log(newData);
    this.setState({
      data: newData
    })
  }

  save() {
    const newData = cloneDeep(this.state.data);
    // remove unset locales
    for(const index of newData.translations.keys()){
      for(const [locale,value] of Object.entries(newData.translations[index].locales)){
        if(!value || value.length === 0){
          delete newData.translations[index].locales[locale];
        }
      }
    }
    this.props.updateTranslations(newData.translations);
    this.setState({
      translations:newData
    })
    this.props.fadeModal();
  }

  render() {
    let fieldData = this.state.data.translations[this.state.selectedFieldIndex];
    if(Object.keys(fieldData.locales).length === 0 && fieldData.locales.constructor === Object){
      fieldData.locales["en"]='';
    }
    const sections = [];
    for(const [data, value] of Object.entries(fieldData.locales)){
      sections.push({
        name:`translations[${this.state.selectedFieldIndex}].locales.${data}`,
        label:`${data}`,
        type:'string'
      });
    }

    const fieldchoices = [];
    this.props.translations.forEach(
      (field, index) => {
        fieldchoices[index] = trans(field.path, {} , this.props.fieldDomain);
      });

    const localeChoices = {};
    this.state.availableLocales.forEach(
      (locale) => {
        localeChoices[locale] = locale;
    });


    return (
      <Modal
        {...omit(this.props, 'translations', 'updateTranslations')}
        icon="fa fa-fw fa-plus"
        title={trans('translation')}
      >
        <div className="modal-body translations-modal">
          <label for="available_fields">{trans('field_name')}</label>
          <Select name="available_fields" 
              id="available_fields"
              noEmpty={true}
              onChange={this.updateSelectedField}
              value={this.state.selectedFieldIndex}
              choices={fieldchoices}
          />
        
        {this.props.defaultValues[fieldData.path] && 
          <Fragment>
          <label>{trans('default_content')}</label>
          <p className="modal-content content-meta"> {this.props.defaultValues[fieldData.path]}</p>
          </Fragment>
        }
        
        <FormData
          level={5}
          data={this.state.data}
          setErrors={() => {}}
          updateProp={this.updateProp}
          sections={[
            {
              id: 'general',
              title: '',
              primary: true,
              fields: sections
            }
          ]}
        />
          <div className="locale-selector">
            <label for="available_locales">{trans('select_new_locale')}</label>
            <Select name="available_locales" 
              id="available_locales"
              noEmpty={true}
              onChange={this.updateSelectedLocale}
              value={this.state.selectedLocale}
              choices={localeChoices}
            />
            <button
              className="btn modal-btn"
              onClick={this.addLocal}
            >
            {trans('add_new_locale')}
            </button>
          </div>
          <button
            className="modal-btn btn"
            onClick={this.save}
          >
            {trans('confirm')}
          </button>
        </div>
      </Modal>
    )}
}

TranslationsModal.propTypes = {
  translations:T.object.isRequired,
  defaultValues:T.Object,
  fieldDomain:T.string,
  updateTranslations:T.func,
  fadeModal: T.func.isRequired
}

export {
  TranslationsModal
}