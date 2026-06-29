import { CancelCircleIcon } from "@hugeicons/core-free-icons"
import ErrorPage from "./ErrorPage"

export default function Forbidden() {
  return (
    <ErrorPage
      code={403}
      icon={CancelCircleIcon}
      title="Access forbidden"
      description="You don't have permission to access this resource."
    />
  )
}
