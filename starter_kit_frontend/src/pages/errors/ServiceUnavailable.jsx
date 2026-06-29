import { Wrench01Icon } from "@hugeicons/core-free-icons"
import ErrorPage from "./ErrorPage"

export default function ServiceUnavailable() {
  return (
    <ErrorPage
      code={503}
      icon={Wrench01Icon}
      title="Service unavailable"
      description="We're down for maintenance and will be back shortly. Thanks for your patience."
    />
  )
}
