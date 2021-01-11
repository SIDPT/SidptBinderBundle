
const STORE_NAME = 'sidpt_document'

const FORM_NAME = `${STORE_NAME}.clarodoc`

const resource = (state) => state[STORE_NAME]

export const selectors = {
  STORE_NAME,
  FORM_NAME,
  resource
}
