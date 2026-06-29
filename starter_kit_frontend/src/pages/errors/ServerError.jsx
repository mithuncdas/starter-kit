import { Alert02Icon } from "@hugeicons/core-free-icons"
import ErrorPage from "./ErrorPage"

export default function ServerError() {
  return (
    <ErrorPage
      code={500}
      icon={Alert02Icon}
      title="Something went wrong"
      description="An unexpected error occurred on our end. Please try again later."
    />
  )
}
