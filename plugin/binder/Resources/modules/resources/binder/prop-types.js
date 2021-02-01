import {PropTypes as T} from 'prop-types'
import {Document} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/prop-types'


const Binder = {
  propTypes: {
    id: T.string.isRequired,
    title: T.string,
    tabs: T.arrayOf(T.object) // Tab types
  },
  defaultProps: {
    tabs: []
  }
}

const Tab = {
  propTypes: {
    id: T.string.isRequired,
    title: T.string,
    slug:T.string,
    metadata: T.shape({
      backgroundColor:T.string,
      borderColor:T.string,
      textColor:T.string,
      icon:T.string,
      details:T.string,
      type:T.oneOf(['document','binder','undefined']),
      roles: T.arrayOf(T.shape({
        id: T.string.isRequired,
        name: T.string.isRequired,
        translationKey: T.string.isRequired,
        type: T.number.isRequired
      }))
    }),
    resourceNode: T.object,
    content: T.object
  },
  defaultProps: {
    metadata:{
      position:0,
      backgroundColor:"white",
      borderColor:"white",
      textColor:"black",
      type:'undefined',
      roles:[]
    },
    content:undefined
  }
}




export {
  Binder,
  Tab
}
